# مرجع لوحة الإحصائيات (Statistics Dashboard) — دليل الفرونت

> **الإصدار:** 1.0  
> **التاريخ:** 2026-07-24  
> **الـ backend:** `Modules/Statistics/`

## نظرة عامة
أربع واجهات منفصلة، كل واحدة لها endpoint group:

| الواجهة | Prefix | Auth | الوصف |
|---------|--------|:---:|-------|
| لوحة التاجر | `/api/dashboard/trader/*` | trader role | KPIs شخصية، رسوم، قوائم، مقارنة، CSV |
| إحصائيات العقار | `/api/dashboard/properties/{id}/stats` | أي مستخدم مسجّل | تفاصيل عقار واحد: قمع، تقييمات، منافسون |
| إحصائيات السوق | `/api/market/*` | **public** | بيانات مجمّعة للزائرين |
| لوحة الأدمن | `/api/admin/statistics/*` | permission: admin_statistics.view | كل المنصّة |

## البنية الموحّدة للاستجابة
كل endpoints الإحصائيات ترجع البنية التالية:

```json
{
  "success": true,
  "data": {
    "filter": {
      "period": "last_30_days",
      "range": {"from": "2026-06-24", "to": "2026-07-24", "days": 30},
      "previous_range": {"from": "2026-05-25", "to": "2026-06-23", "days": 30}
    },
    "generated_at": "2026-07-24T12:00:00+00:00",
    "kpis": [...],
    "charts": [...],
    "distributions": {...},
    "lists": {...}
  }
}
```

## بطاقات KPI (KpiCard)
```json
{
  "key": "total_views",
  "label": "إجمالي المشاهدات",
  "value": 1234,
  "previous_value": 1000,
  "change_percent": 23.4,
  "change_direction": "up",
  "format": "number",
  "icon": "eye"
}
```

### القيم الممكنة لـ `format`
- `number` — عرض كرقم عادي
- `percent` — عرض كنسبة مئوية مع علامة %
- `currency` — عرض كعملة (من price.currency)
- `rating` — عرض كنجوم (من 5)
- `duration` — عرض كزمن (ساعات/أيام)

### القيم الممكنة لـ `change_direction`
- `up` — سهم أخضر (تحسّن)
- `down` — سهم أحمر (تراجع)
- `flat` — خط مستقيم (ثابت)
- `null` — لا مقارنة (لفترات لا تدعم المقارنة)

## الرسوم البيانية (TimeSeries)
```json
{
  "metric": "property_views",
  "unit": "count",
  "points": [
    {"date": "2026-07-01", "count": 10},
    {"date": "2026-07-02", "count": 15}
  ]
}
```

### القيم الممكنة لـ `unit`
- `count` — عدد
- `currency` — مبالغ مالية

## التوزيعات (Distribution)
```json
{
  "dimension": "lead_status",
  "items": [
    {"key": "new", "label": "جديد", "count": 3, "percentage": 60.0},
    {"key": "won", "label": "تم البيع", "count": 2, "percentage": 40.0}
  ],
  "total": 5
}
```

## القوائم (Top List)
```json
{
  "title": "أفضل العقارات أداءً",
  "items": [
    {"id": 1, "name": "...", "views_in_range": 100, "favorites_count": 5}
  ],
  "sort_by": "views_in_range"
}
```

## فلاتر الفترة
كل endpoints الإحصائيات تقبل:
- `?period=today|yesterday|last_7_days|last_30_days|this_week|last_week|this_month|last_month|this_year|custom`
- `?from=YYYY-MM-DD&to=YYYY-MM-DD` (مطلوب عند `period=custom`)

الافتراضي: `last_30_days`.

## الـ Pagination
- Top lists: تمرير `?limit=10` (افتراضي 10)
- لا pagination على KPIs/charts/distributions

## بطاقات الإحصائيات — الألوان والاتجاهات (CSS)
```css
.kpi-card {
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}
.kpi-card .value { font-size: 2rem; font-weight: bold; }
.kpi-card .change.up { color: #10b981; }     /* أخضر */
.kpi-card .change.down { color: #ef4444; }   /* أحمر */
.kpi-card .change.flat { color: #6b7280; }   /* رمادي */
```

## حالة فارغة (Empty State)
عندما لا توجد بيانات:
- `kpis[].value` = 0
- `charts[].points` = مصفوفة بنقاط (count = 0)
- `distributions[].items` = مصفوفة بالعناصر (count = 0)
- `lists[].items` = مصفوفة فارغة

## تصدير CSV
- `GET /api/dashboard/trader/export/properties?period=last_30_days` — يُنزّل CSV مع `Content-Disposition: attachment`.
- الـ frontend يستدعيها كـ `<a href="..." download>` عادية.

## الـ Rate Limiting
كل endpoints الإحصائية تخضع لـ default API rate limiting (60 requests/minute).

## Caching
- الـ backend يخزّن الإحصائيات مؤقتاً:
  - Trader + Property: 5 دقائق
  - Market: 10 دقائق
  - Admin (overview, properties, crm, moderation, communication): 5 دقائق
  - Admin (ads, subscriptions): ساعة (بيانات أثقل)
- لا حاجة للـ frontend لتخزين مؤقت — استدعِ مباشرة.

## Permissions Reference
- `statistics.view` — للوحة التاجر + إحصائيات العقار
- `statistics.export` — تصدير CSV
- `admin_statistics.view` — لوحة الأدمن

## Endpoints Reference (Full List)

### لوحة التاجر (10 endpoints)
| Method | Path | Permission | الوصف |
|--------|------|------------|-------|
| GET | `/api/dashboard/trader/summary` | statistics.view | 7 KPIs رئيسية |
| GET | `/api/dashboard/trader/views-trend` | statistics.view | اتجاه المشاهدات اليومي |
| GET | `/api/dashboard/trader/leads-by-status` | statistics.view | توزيع العملاء المحتملين |
| GET | `/api/dashboard/trader/properties-by-status` | statistics.view | توزيع العقارات |
| GET | `/api/dashboard/trader/top-properties?limit=10` | statistics.view | أفضل العقارات |
| GET | `/api/dashboard/trader/recent-leads?limit=10` | statistics.view | أحدث العملاء |
| GET | `/api/dashboard/trader/upcoming-appointments?days=7` | statistics.view | المواعيد القادمة |
| GET | `/api/dashboard/trader/expiring-rentals?within_days=30` | statistics.view | عقود تنتهي قريباً |
| GET | `/api/dashboard/trader/sponsored-ads-summary` | statistics.view | ملخص إعلانات الرعاية |
| GET | `/api/dashboard/trader/export/properties` | statistics.export | تصدير CSV |

### إحصائيات العقار (1 endpoint)
| Method | Path | Permission | الوصف |
|--------|------|------------|-------|
| GET | `/api/dashboard/properties/{id}/stats` | auth | KPIs + funnel + منافسين + تقييمات |

### إحصائيات السوق (7 endpoints — public)
| Method | Path | الوصف |
|--------|------|-------|
| GET | `/api/market/overview` | إجمالي العقارات، متوسط السعر، مباعة |
| GET | `/api/market/by-city` | توزيع حسب المدينة |
| GET | `/api/market/by-category` | توزيع حسب الفئة |
| GET | `/api/market/by-price-range` | 5 شرائح سعرية |
| GET | `/api/market/top-viewed?limit=10` | أكثر 10 مشاهدة |
| GET | `/api/market/top-saved?limit=10` | أكثر 10 في المفضلات |
| GET | `/api/market/listings-trend` | اتجاه الإضافة اليومي |

### لوحة الأدمن (7 endpoints)
| Method | Path | Permission | الوصف |
|--------|------|------------|-------|
| GET | `/api/admin/statistics/overview` | admin_statistics.view | KPIs عامة + trends |
| GET | `/api/admin/statistics/properties` | admin_statistics.view | توزيع + أسباب رفض + أكثر تجار |
| GET | `/api/admin/statistics/crm` | admin_statistics.view | معدل التحويل + مصادر |
| GET | `/api/admin/statistics/ads` | admin_statistics.view | إيرادات إعلانات + CPV |
| GET | `/api/admin/statistics/subscriptions` | admin_statistics.view | MRR + Churn + اتجاه |
| GET | `/api/admin/statistics/moderation` | admin_statistics.view | طوابير المراجعة |
| GET | `/api/admin/statistics/communication` | admin_statistics.view | رسائل + محادثات |
