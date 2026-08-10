# Graduation Project — Diagrams & Reports

هذه الملفات جاهزة للاستيراد داخل المواقع لعرض المخططات. كل ملف مكتوب بلغة نصية (code) تدعمها مواقع العرض، فقط انسخ المحتوى أو ارفعه حسب الخطوات بالأسفل.

## أفضل المواقع والطريقة (ملخص)

| المخطط | أفضل موقع | الملف | طريقة الاستيراد |
|---|---|---|---|
| **ER Diagram** | [dbdiagram.io](https://dbdiagram.io) (الأفضل للعرض) | `er-diagram.dbml` | افتح الموقع → New → الصق الكود |
| ER Diagram (بديل) | [mermaid.live](https://mermaid.live) | `er-diagram.mmd` | الصق الكود في محرر Mermaid |
| **Use Case** | [PlantUML](https://www.plantuml.com/plantuml) | `use-case.puml` | الصق الكود في مربع PlantUML |
| Use Case (بديل) | [mermaid.live](https://mermaid.live) | `use-case.mmd` | الصق الكود في محرر Mermaid |
| **Sequence** | [mermaid.live](https://mermaid.live) | `sequence-diagrams.mmd` | الصق الكود في محرر Mermaid |
| **Class** | [mermaid.live](https://mermaid.live) | `class-diagram.mmd` | الصق الكود في محرر Mermaid |
| **Tech Stack** | أي محرر Markdown (Notion / GitHub / Typora) | `tech-stack.md` | افتح/اعرض الملف مباشرة |

## خطوات عامة

1. **mermaid.live** → افتح الموقع → احذف المثال الافتراضي → الصق محتوى ملف `.mmd` → سيُعرض الرسم فورًا → زر **Download SVG/PNG** للتصدير.
2. **dbdiagram.io** → سجّل (مجاني) → New Diagram → الصق محتوى `.dbml` → تُحفظ تلقائيًا وتُصدّر PNG/SQL.
3. **PlantUML** → افتح [plantuml.com](https://www.plantuml.com/plantuml) → الصق الكود → **Submit** → تظهر الصورة.
4. **draw.io / diagrams.net** (اختياري): يدعم `Arrange → Insert → Advanced → PlantUML` و `Mermaid`.

## نصيحة للعرض في التقرير

- صدّر كل مخطط بصيغة **PNG/SVG عالية الجودة** من الموقع، ثم أضفها لملف التقرير.
- عند عمل Use Case احرص على ذكر الـ Actors الأساسية: **Guest، User، Publisher (ناشر)، Service Provider (مقدم خدمة)، Admin**.
- كل مخطط في هذا المجلد مبنى على الكود الفعلي للمشروع (الجداول، الـ Routes، الـ Entities).
