# الفهرس الرئيسي - خطط المهام المعمارية

**التاريخ:** 26 يونيو 2026  
**المصادر:** `*.md` (تقارير)  
**الهدف:** 

---

## 📊 ملخص الحالة الراهنة (2026-06-26)

| المؤشر | القيمة |
|--------|--------|
| إجمالي الخطط | **15 خطة** |
| ✅ مكتمل | 15 |
| ❌ لم يبدأ | 0 |
| 🟡 جزئياً | 0 |

---

## 🗺️ خريطة التبعيات والتنفيذ بالتوازي

### خريطة الـ CRM (تقارير mini-crm)

```
#10 (CRM Foundation)  ──→  #12 (Leads)  ──→  #13 (Notes)
        │                    │
        │                    └──────────→  #14 (Dashboard)
        │
#11 (Appointment)  ──────────────────────→ (يُستهلك في #12 polymorphic)
```

### خريطة بطاقات التأجير (تقرير rental-cards)

```
#15 (Data Foundation)  ──→  #16 (Service + Status)  ──→  #17 (API Surface)  ──→  #19 (Tests)
                                  │                          │
                                  │                          └──────────→  #18 (Pre-Rental Photos)
                                  │                                     │
                                  └─────────────────────────────────────┘
                                            (تعمل بالتوازي)
```

---

## 🌊 موجات التنفيذ المقترحة

### الموجة 1 — يمكن البدء فورًا (لا تبعيات)
- **#10** تأسيس وحدة CRM + دور trader + صلاحيات crm.*
- **#11** إعادة هيكلة PropertyViewing → Appointment (polymorphic)
- **#15** تأسيس بيانات بطاقات التأجير (Migration + Model + Enum + DTO + Permissions)
- **#20** تأسيس بيانات نظام الملفات (Migration + Models + Enums + DTOs + Permissions)

### الموجة 2 — تعتمد على اكتمال الموجة 1
- **#12** إدارة العملاء المحتملين (Leads) — تعتمد على #10 و #11
- **#16** خدمة بطاقات التأجير + ربط حالة العقار — تعتمد على #15
- **#21** الخدمة الأساسية وسطح API لنظام الملفات — تعتمد على #20

### الموجة 3 — تعتمد على اكتمال الموجة 2
- **#13** ملاحظات العملاء (تعمل بالتوازي مع #14) — تعتمد على #12
- **#14** لوحة اليوم + تصدير CSV (تعمل بالتوازي مع #13) — تعتمد على #12
- **#17** سطح API لبطاقات التأجير — تعتمد على #15 و #16
- **#22** دمج مجلدات العقارات + **#23** نظام حصة التخزين — تعتمدان على #21 (بالتوازي)

### الموجة 4 — تعتمد على اكتمال الموجة 3
- **#18** صور "قبل التأجير" عبر نظام الملفات — تعتمد على #15، #17، وميزة `per-user-file-system`
- **#19** اختبارات قبول شاملة + توثيق Scribe — تعتمد على #15، #16، #17، #18
- **#24** اختبارات قبول شاملة + توثيق Scribe لنظام الملفات — تعتمد على #20، #21، #22، #23

---

## 📅 جدول التنفيذ المقترح (6 أسابيع بثلاثة مسارات متوازية)

| الأسبوع | المسار A (CRM) | المسار B (Rental Cards) | المسار C (File System) |
|---------|----------------|-------------------------|-------------------------|
| الأسبوع 1 | #10 كل المراحل (4–6h) | #15 كل المراحل (5–8h) | #20 كل المراحل (4–6h) |
| الأسبوع 2 | #11 كل المراحل (4–6h) | #16 كل المراحل (12–16h) | #21 كل المراحل (12–16h) |
| الأسبوع 3 | #12 كل المراحل (10–14h) | #17 كل المراحل (12–16h) | — |
| الأسبوع 4 | #13 (5h) + #14 (4h) بالتوازي | #18 (8–12h) + #19 (12–16h) بالتوازي | #22 (6h) + #23 (6h) بالتوازي |
| الأسبوع 5 | — | — | #22 + #23 (تابع) |
| الأسبوع 6 | — | — | #24 (8–12h) |

**إجمالي الجهد:** ~29 ساعة (CRM) + ~50 ساعة (Rental Cards) + ~36 ساعة (File System) موزعة على 6 أسابيع بثلاثة مسارات متوازية.

---

### خريطة نظام الملفات (تقرير per-user-file-system)

```
#20 (Data Foundation)  ──→  #21 (Core Service + API)
                                   │
                       ┌───────────┼───────────┐
                       ▼           ▼           ▼
                 #22 (Property)  #23 (Quota)  (بالتوازي)
                       │           │
                       └─────┬─────┘
                             ▼
                       #24 (Tests + Docs)
```

---

## 🎉 رحلتي mini-crm + rental-cards مكتملتان (10/10)

تم تنفيذ الخطتين بالكامل (mini-crm #10-#14 و rental-cards #15-#19). كل ميزة جاهزة للإنتاج مع اختبارات شاملة وتوثيق Scribe.

---

## 🎉 جميع المهام مكتملة (15/15)

### mini-crm (تقرير: `docs/ideas/mini-crm/report.md`)
* [plan10-crm-module-foundation-and-trader-role.md](10-crm-module-foundation-and-trader-role.md)... ✅
* [plan11-generic-appointment-refactor.md](11-generic-appointment-refactor.md)... ✅
* [plan12-leads-management.md](12-leads-management.md)... ✅
* [plan13-lead-notes.md](13-lead-notes.md)... ✅
* [plan14-crm-dashboard-and-export.md](14-crm-dashboard-and-export.md)... ✅

### rental-cards (تقرير: `docs/ideas/rental-cards/report.md`)
* [plan15-rental-cards-data-foundation.md](15-rental-cards-data-foundation.md)... ✅
* [plan16-rental-cards-service-and-status-integration.md](16-rental-cards-service-and-status-integration.md)... ✅
* [plan17-rental-cards-api-surface.md](17-rental-cards-api-surface.md)... ✅
* [plan18-rental-cards-pre-rental-photos.md](18-rental-cards-pre-rental-photos.md)... ✅
* [plan19-rental-cards-feature-tests-and-docs.md](19-rental-cards-feature-tests-and-docs.md)... ✅

### per-user-file-system (تقرير: `docs/ideas/per-user-file-system/report.md`)
* [plan20-per-user-file-system-data-foundation.md](20-per-user-file-system-data-foundation.md)... ✅
* [plan21-per-user-file-system-core-service-and-api.md](21-per-user-file-system-core-service-and-api.md)... ✅
* [plan22-per-user-file-system-property-integration.md](22-per-user-file-system-property-integration.md)... ✅
* [plan23-per-user-file-system-storage-quota.md](23-per-user-file-system-storage-quota.md)... ✅
* [plan24-per-user-file-system-tests-and-docs.md](24-per-user-file-system-tests-and-docs.md)... ✅
