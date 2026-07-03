# الفهرس الرئيسي - خطط المهام المعمارية

**التاريخ:** 26 يونيو 2026  
**المصادر:** `*.md` (تقارير)  
**الهدف:** 

---

## 📊 ملخص الحالة الراهنة (2026-06-26)

| المؤشر | القيمة |
|--------|--------|
| إجمالي الخطط | **10 خطط** |
| ✅ مكتمل | 10 |
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

### الموجة 2 — تعتمد على اكتمال الموجة 1
- **#12** إدارة العملاء المحتملين (Leads) — تعتمد على #10 و #11
- **#16** خدمة بطاقات التأجير + ربط حالة العقار — تعتمد على #15

### الموجة 3 — تعتمد على اكتمال الموجة 2
- **#13** ملاحظات العملاء (تعمل بالتوازي مع #14) — تعتمد على #12
- **#14** لوحة اليوم + تصدير CSV (تعمل بالتوازي مع #13) — تعتمد على #12
- **#17** سطح API لبطاقات التأجير — تعتمد على #15 و #16

### الموجة 4 — تعتمد على اكتمال الموجة 3
- **#18** صور "قبل التأجير" عبر نظام الملفات — تعتمد على #15، #17، وميزة `per-user-file-system`
- **#19** اختبارات قبول شاملة + توثيق Scribe — تعتمد على #15، #16، #17، #18

---

## 📅 جدول التنفيذ المقترح (4 أسابيع بمسارين متوازيين)

| الأسبوع | المسار A (CRM) | المسار B (Rental Cards) |
|---------|----------------|-------------------------|
| الأسبوع 1 | #10 كل المراحل (4–6h) | #15 كل المراحل (5–8h) |
| الأسبوع 2 | #11 كل المراحل (4–6h) | #16 كل المراحل (12–16h) |
| الأسبوع 3 | #12 كل المراحل (10–14h) | #17 كل المراحل (12–16h) |
| الأسبوع 4 | #13 (5h) + #14 (4h) بالتوازي | #18 (8–12h) + #19 (12–16h) بالتوازي |

**إجمالي الجهد:** ~29 ساعة (CRM) + ~50 ساعة (Rental Cards) موزعة على 4 أسابيع بمسارين متوازيين.

---

## 🎉 رحلتي mini-crm + rental-cards مكتملتان (10/10)

تم تنفيذ الخطتين بالكامل (mini-crm #10-#14 و rental-cards #15-#19). كل ميزة جاهزة للإنتاج مع اختبارات شاملة وتوثيق Scribe.

---

## ❌ المهام المتبقية (5 خطط)

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
