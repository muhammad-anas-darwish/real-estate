# Graduation Project — Diagrams & Reports

كل المخططات في هذا المجلد مبنية على **الكود الفعلي الحالي للمشروع** (بعد دمج `develop` في `main`): 12 وحدة، 55 جدولاً، 43 enum، و262 endpoint.

كل ملف نصي (code) جاهز للاستيراد داخل برنامج/موقع عرض. اختر من الجدول أدناه.

## 📋 دليل البرامج لكل ملف

| الملف | ما يعرضه | البرنامج/الموقع الأفضل | طرق بديلة | طريقة الاستيراد |
|---|---|---|---|---|
| `er-diagram.dbml` | مخطط ER (55 جدول) | **dbdiagram.io** | QuickDBD، dbdocs.io | New → الصق الكود |
| `er-diagram.mmd` | مخطط ER (بديل) | **mermaid.live** | GitHub، Notion، draw.io | الصق الكود في المحرر |
| `use-case.puml` | مخطط Use Case | **PlantUML** (plantuml.com) | draw.io، VS Code extension | الصق الكود → Submit |
| `use-case.mmd` | مخطط Use Case (بديل) | **mermaid.live** | GitHub، Notion | الصق الكود في المحرر |
| `sequence-diagrams.mmd` | 8 مخططات Sequence | **mermaid.live** | GitHub، Notion | الصق مخطط واحد في كل مرة |
| `class-diagram.mmd` | مخطط Class (الطبقات) | **mermaid.live** | GitHub، Notion | الصق الكود في المحرر |
| `tech-stack.md` | التقنيات والاعتماديات | **Notion / GitHub / Typora** | أي محرر Markdown | افتح الملف مباشرة |

## 🖥️ خطوات الاستيراد لكل برنامج

### 1) mermaid.live (الأكثر استخداماً — يغطي 4 مخططات)
1. افتح [mermaid.live](https://mermaid.live)
2. احذف المثال الافتراضي
3. الصق محتوى ملف `.mmd` المطلوب
4. الرسم يظهر فوراً → زر **Download SVG/PNG** للتصدير

### 2) dbdiagram.io (الأفضل لمخطط ER)
1. سجّل مجاناً في [dbdiagram.io](https://dbdiagram.io)
2. اضغط **New Diagram**
3. الصق محتوى `er-diagram.dbml`
4. المخطط يُرسم تلقائياً → زر **Export PNG** و **Export SQL**

### 3) PlantUML (الأفضل لـ Use Case)
1. افتح [plantuml.com](https://www.plantuml.com/plantuml)
2. الصق محتوى `use-case.puml`
3. اضغط **Submit** → تظهر الصورة → احفظها PNG/SVG

### 4) draw.io / diagrams.net (بديل جامعي)
- **PlantUML**: قائمة `Arrange → Insert → Advanced → PlantUML` ثم الصق كود `use-case.puml`
- **Mermaid**: قائمة `Arrange → Insert → Advanced → Mermaid` ثم الصق أي ملف `.mmd`

### 5) GitHub / Notion (عرض مباشر بدون مواقع خارجية)
- ارفع ملفات `.mmd` لمستودع GitHub — يتحول لمخططات تلقائياً
- أو أنشئ كتلة **Code → mermaid** في Notion والصق الكود

## 📌 ملاحظات هامة

- **Sequence Diagrams**: ملف `sequence-diagrams.mmd` يحتوي 8 مخططات منفصلة — الصق **مخططاً واحداً فقط** في كل مرة (اختر الجزء بين `%% Diagram N` والتعليق التالي).
- **التصدير للتقرير**: بعد العرض، استخدم **Export PNG** أو **Download SVG** بجودة عالية وأدرجها في ملف التقرير النهائي.
- **مصدر المخططات**: كلها مولّدة من الكود الفعلي — ER من الـ Migrations، Use Case من الـ Routes، Class من الـ Controllers/Services/Entities.
- **المشهد الحالي**: 12 وحدة (Ai, Auth, Core, RealEstate, Communication, Subscription, Deposit, ServiceProvider, Crm, FileSystem, Ledger, Statistics) + Map ضمن RealEstate.
