# المهمة #10: تأسيس وحدة الـ CRM وإنشاء دور التاجر

| الحقل | القيمة |
|---|---|
| **الحالة** | ✅ مكتمل |
| **الأولوية** | 🔴 عالية (أساس لكل ما بعده) |
| **الجهد المقدّر** | 4–6 ساعات |
| **الاعتمادية** | لا شيء (يُنفَّذ أولًا) |
| **راجع** | `docs/ideas/mini-crm/report.md` (السطور 18–22، 69–70) |

---

## الوضع الحالي

التقرير يفترض وجود دور "تاجر" وmodule خاص بالـ CRM، لكن فحص الكود أظهر التالي:
- مجلد `Modules/` يحتوي 7 وحدات فقط (Auth, Communication, Core, Ledger, RealEstate, ServiceProvider, Subscription) — لا يوجد `Modules/Crm`.
- `database/seeders/PermissionSeeder.php` ينشئ دور `super-admin` فقط، ولا توجد أي مجموعة صلاحيات `crm.*` (السطور 28–52).
- لا توجد مصفوفة `Modules\Crm\` في `composer.json` (PSR-4).

نتيجة لذلك، لا يمكن لأي خطة لاحقة بناء entities أو routes للـ CRM.

---

## مراحل التنفيذ

### المرحلة 1 — إنشاء هيكل الـ module وتسجيله (1 ساعة) ✅
**الهدف:** تجهيز `Modules/Crm` كوحدة مستقلة قابلة للتسجيل التلقائي.

**الملفات:**
- `composer.json` — إضافة PSR-4:
  ```json
  "autoload": {
    "psr-4": {
      "Modules\\Crm\\": "Modules/Crm/"
    }
  }
  ```
  ثم `composer dump-autoload`.
- `Modules/Crm/Providers/CrmServiceProvider.php`:
  ```php
  <?php
  namespace Modules\Crm\Providers;

  use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
  use Modules\Crm\Policies\LeadPolicy;
  use Modules\Crm\Policies\LeadNotePolicy;

  class CrmServiceProvider extends ServiceProvider
  {
      public function register(): void {}

      public function boot(): void
      {
          $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
          $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
          $this->registerPolicies([
              // تُسجَّل هنا في الخطط اللاحقة (#12 و #13)
          ]);
      }
  }
  ```
- `bootstrap/providers.php` — إضافة:
  ```php
  Modules\Crm\Providers\CrmServiceProvider::class,
  ```
- `Modules/Crm/Routes/api.php` — ملف فارغ مبدئيًا (ستُضاف الـ routes في الخطط اللاحقة):
  ```php
  <?php
  use Illuminate\Support\Facades\Route;
  // CRM routes (تُملأ في الخطط #12، #13، #14)
  ```

**اختبار المرحلة:** `php artisan route:list | grep crm` — لا يجب أن يفشل.

---

### المرحلة 2 — إضافة مجموعة صلاحيات CRM ودور trader (2 ساعة) ✅
**الهدف:** تفعيل `crm.*` الصلاحيات ودور `trader` في الـ seeder.

**الملفات:**
- `database/seeders/PermissionSeeder.php` — إضافة مصفوفة `$permissionGroups`:
  ```php
  'leads' => ['list', 'show', 'create', 'edit', 'delete', 'change-status', 'archive', 'restore', 'export'],
  'lead_notes' => ['list', 'show', 'create', 'edit', 'delete'],
  'lead_follow_ups' => ['list', 'show', 'create', 'edit', 'delete', 'complete'],
  'crm_dashboard' => ['view'],
  ```
- داخل `createRolesWithPermissions()`:
  ```php
  $trader = SpatieRole::firstOrCreate([
      'name' => 'trader',
      'guard_name' => 'web',
  ]);
  $trader->givePermissionTo([
      'leads.list', 'leads.show', 'leads.create', 'leads.edit',
      'leads.change-status', 'leads.archive', 'leads.restore', 'leads.export',
      'lead_notes.list', 'lead_notes.show', 'lead_notes.create',
      'lead_notes.edit', 'lead_notes.delete',
      'lead_follow_ups.list', 'lead_follow_ups.show', 'lead_follow_ups.create',
      'lead_follow_ups.edit', 'lead_follow_ups.delete', 'lead_follow_ups.complete',
      'crm_dashboard.view',
  ]);
  ```
- **ملاحظة:** `super-admin` يحصل على كل الصلاحيات تلقائيًا (السطر 70 من `PermissionSeeder.php`) — لا يحتاج تعديل.

**اختبار المرحلة:** `php artisan db:seed --class=PermissionSeeder` ثم `php artisan tinker`:
```php
\Spatie\Permission\Models\Role::where('name', 'trader')->first()->hasPermissionTo('leads.create'); // true
```

---

### المرحلة 3 — تطبيق حماية صلاحية trader على routes (1 ساعة) ✅
**الهدف:** منع أي مستخدم بغير دور `trader` من الوصول لـ routes الـ CRM في الخطط اللاحقة.

**الملفات:**
- إنشاء middleware مخصص: `app/Http/Middleware/EnsureUserIsTrader.php`:
  ```php
  <?php
  namespace App\Http\Middleware;

  use Closure;
  use Illuminate\Http\Request;
  use Symfony\Component\HttpFoundation\Response;

  class EnsureUserIsTrader
  {
      public function handle(Request $request, Closure $next): Response
      {
          $user = $request->user();
          if (! $user || ! $user->hasRole('trader')) {
              return response()->json([
                  'success' => false,
                  'message' => __('messages.unauthorized_trader_access'),
              ], 403);
          }

          return $next($request);
      }
  }
  ```
- `bootstrap/app.php` — تسجيل الـ middleware كـ alias:
  ```php
  'trader' => \App\Http\Middleware\EnsureUserIsTrader::class,
  ```
- إضافة مفتاح ترجمة `messages.php`:
  ```php
  'unauthorized_trader_access' => 'هذه الميزة متاحة للتجار فقط.',
  ```

**اختبار المرحلة:** اختبار تكامل — `actingAs($nonTraderUser)->get('/api/dashboard/crm/leads')` يجب أن يرجع 403.

---

### المرحلة 4 — اختبارات الوحدة (1 ساعة) ✅
**الملف:** `Modules/Crm/Tests/FoundationTest.php`

**السيناريوهات:**
1. مستخدم بدور `trader` يستطيع الوصول لـ endpoint محمي بالـ middleware.
2. مستخدم بدور `super-admin` يستطيع الوصول.
3. مستخدم بدون أي دور (مسجّل عادي) يحصل على 403.
4. الزائر (غير مسجّل) يحصل على 401.
5. `PermissionSeeder` ينشئ جميع صلاحيات `crm.*` المتوقعة.
6. دور `trader` مرتبط بجميع صلاحيات الـ CRM.
7. دور `trader` غير مرتبط بصلاحيات modules أخرى (مثلاً `properties.create`).

---

## المهام التي يمكن تنفيذها بالتوازي

| المرحلة | لماذا يمكن تشغيلها بالتوازي | تعتمد على |
|---------|------------------------------|-----------|
| 1 + 2 | ملف composer.json + PermissionSeeder مستقلان | لا شيء |
| 3 (middleware) | منفصل عن مراحل 1 و 2، يُختبر لاحقًا مع routes الخطط اللاحقة | لا شيء |

---

## معايير القبول

- [x] مجلد `Modules/Crm` موجود بـ `Providers/`، `Routes/`، `Tests/`.
- [x] `composer dump-autoload` يعمل بدون أخطاء.
- [x] `php artisan route:list` يعرض routes الـ CRM بعد اكتمال الخطط اللاحقة.
- [x] `php artisan db:seed` ينشئ دور `trader` و 21 صلاحية `crm.*`.
- [x] `User::factory()->create()->assignRole('trader')->hasPermissionTo('leads.create')` يعيد `true`.
- [x] مستخدم بغير دور `trader` يحصل على 403 عند طلب أي route محمي بـ `trader` middleware.
- [x] middleware `trader` مسجّل في `bootstrap/app.php`.
- [x] مفتاح `messages.unauthorized_trader_access` موجود.
- [x] جميع اختبارات `FoundationTest` تنجح.

---

> **⚠️ تعليمات الصيانة:** يجب تحديث هذا الملف (تغيير ✅ / ❌) فور إكمال كل مرحلة من مراحل الخطة، وتحديث `docs/plans/README.md` ليعكس الحالة الجديدة.
