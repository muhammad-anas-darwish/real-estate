# المهمة #14: لوحة "اليوم" للـ CRM وتصدير CSV

| الحقل | القيمة |
|---|---|
| **الحالة** | ❌ لم يبدأ |
| **الأولوية** | 🟡 متوسطة |
| **الجهد المقدّر** | 4–6 ساعات |
| **الاعتمادية** | يجب أن يسبقها #12 (للـ Lead) — لا يحتاج #13 |
| **راجع** | `docs/ideas/mini-crm/report.md` (السيناريو 5؛ القواعد 16، 19–21؛ التدفّق 6–7) |

---

## الوضع الحالي

التقرير يطلب ثلاث ميزات UX (السطور 124–128):
1. **بطاقات إحصائية** في أعلى اللوحة (عملاء جدد هذا الأسبوع، مواعيد اليوم، متأخرات).
2. **تبويب "اليوم"** للمواعيد المستحقة والعملاء المتأخرين.
3. **تصدير CSV** لقائمة العملاء.

لا يوجد حاليًا أي endpoint للإحصائيات في الـ CRM (لا حاجة له — الميزة جديدة).

---

## مراحل التنفيذ

### المرحلة 1 — Service للإحصائيات (1.5 ساعة)

**الملف:** `Modules/Crm/Services/LeadStatsService.php`:
```php
<?php
namespace Modules\Crm\Services;

use App\Services\BaseService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Crm\Enums\LeadStatus;
use Modules\Crm\Entities\Lead;
use Modules\RealEstate\Entities\Appointment;
use Carbon\Carbon;

class LeadStatsService extends BaseService
{
    protected const CACHE_TTL_SECONDS = 300;
    protected const CACHE_TAG = 'crm_stats';

    public function summary(int $traderId): array
    {
        $cacheKey = $this->generateCacheKey(['trader' => $traderId], 'summary');
        return Cache::tags(self::CACHE_TAG)->remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($traderId) {
            return [
                'new_leads_this_week' => Lead::where('trader_id', $traderId)
                    ->where('created_at', '>=', now()->subWeek())
                    ->count(),
                'today_follow_ups' => Appointment::where('agent_id', $traderId)
                    ->where('type', 'follow_up')
                    ->whereDate('scheduled_at', today())
                    ->whereNotIn('status', ['cancelled', 'completed'])
                    ->count(),
                'overdue_follow_ups' => Appointment::where('agent_id', $traderId)
                    ->where('type', 'follow_up')
                    ->where('scheduled_at', '<', now())
                    ->whereNotIn('status', ['cancelled', 'completed'])
                    ->count(),
                'leads_by_status' => Lead::where('trader_id', $traderId)
                    ->active()
                    ->groupBy('status')
                    ->select('status', DB::raw('count(*) as count'))
                    ->pluck('count', 'status'),
            ];
        });
    }

    public function today(int $traderId): array
    {
        $cacheKey = $this->generateCacheKey(['trader' => $traderId, 'date' => today()->toDateString()], 'today');
        return Cache::tags(self::CACHE_TAG)->remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($traderId) {
            $todayAppointments = Appointment::where('agent_id', $traderId)
                ->where('type', 'follow_up')
                ->whereDate('scheduled_at', today())
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->with('followable')
                ->orderBy('scheduled_at')
                ->get();

            $overdueAppointments = Appointment::where('agent_id', $traderId)
                ->where('type', 'follow_up')
                ->where('scheduled_at', '<', now())
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->with('followable')
                ->orderBy('scheduled_at')
                ->get();

            return [
                'today_appointments' => $todayAppointments,
                'overdue_appointments' => $overdueAppointments,
            ];
        });
    }
}
```

**ملاحظة:** `Lead::touchActivity()` و `AppointmentService::complete()` يجب أن يستدعيا `LeadStatsService::clearCache()` عند التحديث — يُربط في `AppointmentService` كـ listener.

---

### المرحلة 2 — Controllers (1.5 ساعة)

**الملفات:**
- `Modules/Crm/Http/Controllers/LeadDashboardController.php`:
  ```php
  public function summary(LeadStatsService $stats)
  {
      return $this->successResponse($stats->summary(auth()->id()));
  }

  public function today(LeadStatsService $stats)
  {
      return $this->successResponse($stats->today(auth()->id()));
  }
  ```
- `Modules/Crm/Http/Controllers/LeadExportController.php`:
  ```php
  public function csv(Request $request)
  {
      $leads = Lead::where('trader_id', auth()->id())
          ->active()
          ->filter()
          ->orderByDesc('last_activity_at')
          ->get();

      $filename = 'leads-export-'.now()->format('Ymd-His').'.csv';
      $headers = [
          'Content-Type' => 'text/csv; charset=UTF-8',
          'Content-Disposition' => "attachment; filename=\"$filename\"",
      ];

      $callback = function () use ($leads) {
          $file = fopen('php://output', 'w');
          fputcsv($file, ['ID', 'Name', 'Phone', 'Email', 'Source', 'Status', 'Lost Reason', 'Created At', 'Last Activity']);
          foreach ($leads as $lead) {
              fputcsv($file, [
                  $lead->id,
                  $lead->name,
                  $lead->phone,
                  $lead->email,
                  $lead->source?->value,
                  $lead->status?->value,
                  $lead->lost_reason,
                  $lead->created_at->format('Y-m-d H:i:s'),
                  $lead->last_activity_at?->format('Y-m-d H:i:s'),
              ]);
          }
          fclose($file);
      };

      return response()->stream($callback, 200, $headers);
  }
  ```

---

### المرحلة 3 — Routes (0.5 ساعة)

**إضافات على `Modules/Crm/Routes/api.php`:**
```php
Route::get('dashboard/summary', [LeadDashboardController::class, 'summary'])->name('api.crm.dashboard.summary');
Route::get('dashboard/today', [LeadDashboardController::class, 'today'])->name('api.crm.dashboard.today');
Route::get('leads/export', [LeadExportController::class, 'csv'])->name('api.crm.leads.export');
```

**ملاحظة مهمة:** `/leads/export` يجب أن يكون قبل `/leads/{id}` في تعريف الـ routes حتى لا يُفسّر "export" كـ `{id}`.

---

### المرحلة 4 — اختبارات (1.5 ساعة)

**الملف:** `Modules/Crm/Tests/LeadDashboardTest.php`:
1. `GET /dashboard/summary` لتاجر → 200 مع المفاتيح الأربعة.
2. `GET /dashboard/summary` لمستخدم غير تاجر → 403.
3. `summary` يحتوي `new_leads_this_week` صحيح (يُنشئ 3 leads في نفس الأسبوع).
4. `summary.today_follow_ups` يحسب فقط مواعيد اليوم.
5. `summary.overdue_follow_ups` يحسب فقط المواعيد السابقة.
6. `GET /dashboard/today` يدمج `today_appointments` و `overdue_appointments`.
7. الكاش: استدعاءان متتاليان لنفس trader يعيدان نفس النتيجة من الكاش (يُختبر بـ `Cache::shouldReceive`).
8. `clearCache` يُستدعى عند إضافة lead جديد.

**الملف:** `Modules/Crm/Tests/LeadExportTest.php`:
1. تصدير بدون leads → CSV بـ header فقط.
2. تصدير بـ 5 leads → CSV بـ 6 أسطر (header + 5).
3. تاجر آخر لا يظهر في التصدير.
4. تصفية `?status=new` تنعكس في التصدير.
5. الـ Content-Disposition header صحيح.

---

## المهام التي يمكن تنفيذها بالتوازي

| المرحلة | لماذا يمكن تشغيلها بالتوازي | تعتمد على |
|---------|------------------------------|-----------|
| 1 + 2 | الـ Controllers تستخدم Service فقط | يعتمد على 1 |
| 3 | بعد 2 | يعتمد على 2 |
| 4 | بعد 3 | يعتمد على 3 |

---

## معايير القبول

- [ ] `GET /api/dashboard/crm/dashboard/summary` يعيد JSON بمفاتيح `new_leads_this_week`, `today_follow_ups`, `overdue_follow_ups`, `leads_by_status`.
- [ ] `GET /api/dashboard/crm/dashboard/today` يعيد `today_appointments` و `overdue_appointments`.
- [ ] `GET /api/dashboard/crm/leads/export` يُنزّل ملف CSV مع headers صحيحة.
- [ ] الملف يحتوي header + leads فقط، لا يحتوي على تاجر آخر.
- [ ] التصفية بـ `?status=new` تنعكس في CSV.
- [ ] الكاش يعمل (5 دقائق TTL) ويُمسح عند الإضافة/التعديل.
- [ ] `php artisan test --testsuite=Modules --filter="LeadDashboardTest|LeadExportTest"` ينجح.

---

> **⚠️ تعليمات الصيانة:** يجب تحديث هذا الملف (تغيير ✅ / ❌) فور إكمال كل مرحلة من مراحل الخطة، وتحديث `docs/plans/README.md` ليعكس الحالة الجديدة.
