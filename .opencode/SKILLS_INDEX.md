# OpenCode Skills — فهرس محلي

> **ملاحظة:** هذا الملف داخل مجلد `.opencode/` الذي هو مضاف إلى `.gitignore`، فهو مرجعك الشخصي ولا يدخل في commits المشروع.

---

## كيف تستدعي أي skill؟

فقط اكتب في الـ chat بصيغ قريبة من الـ trigger. مثلاً:
- "اعمل commit" → `commit-message`
- "ابحث عن N+1" → `query-reviewer`
- "سجّل bug" → `bug-report-formatter`

---

## 📋 فهرس الـ Skills

### 🛠️ Dev workflow (يومي)

| Skill | الوظيفة | Trigger |
|-------|---------|---------|
| `commit-message` | Conventional Commits بالإنجليزي، `git add -A` تلقائي | `commit`, `اعمل commit`, `احفظ التغييرات` |
| `flow-reviewer` | تتبّع سيناريو عبر كل الـ layers (route → controller → service → DB) | `اشرح flow`, `trace the X flow`, `اي ملفات تتأثر` |
| `launch-checklist` | checklist مخصّص قبل النشر + variant hotfix | `launch checklist`, `جاهز للنشر؟`, `pre-flight` |
| `i18n-extractor` | يجد نصوص مثبتة في Blade/Vue + يقترح keys جاهزة | `ابحث عن النصوص المثبتة`, `i18n cleanup` |
| `query-reviewer` | كشف N+1، indexes مفقودة، raw SQL، queries داخل loops | `ابحث عن N+1`, `audit queries`, `مراجعة أداء قاعدة البيانات` |
| `test-case-generator` | Pest/PHPUnit tests — happy + edge + failure + auth | `اكتب اختبارات لـ X`, `generate tests` |

### 🐛 Bug tracking (محلي، gitignored)

| Skill | الوظيفة | Trigger |
|-------|---------|---------|
| `bug-report-formatter` | يحفظ bug في `docs/bugs/` بحالة open + tags + 3 ملفات مشتبه بها | `سجّل bug`, `احفظ الخطأ`, `وثّق هذا` |
| `bug-resolver` | يحقق، يصلح، يحدّث ملف الـ bug → `resolved` | `احصل على bug`, `fix BUG-...`, `ابحث عن السبب` |

### 🏗️ Refactor / docs / execution

| Skill | الوظيفة | Trigger |
|-------|---------|---------|
| `feature-report` | يأخذ فكرة ميزة صغيرة وينتج تقرير أعمال non-technical في `docs/ideas/<slug>/report.md` (مراجعة قبل التخطيط) | `لدي فكرة`, `سجّل فكرة`, `feature report`, `عندي ميزة أريد إضافتها` |
| `plan-generator` | يفكك التقرير لخطة منفصلة لكل مشكلة رئيسية (يعرض خطة التقسيم قبل الكتابة) | `اعمل plan لملف X`, `create plans for X`, `فكك تقرير X إلى خطط` |
| `plan-executor` | ينفذ الخطة مرحلة بمرحلة، يعلّم ✅ ويحدّث README | `نفذ خطة 24`, `implement plan 24`, `ابنِ خطة X` |
| `plan-reviewer` | يراجع التنفيذ: أخطاء؟ مطابقة للخطة؟ مشكلة التقرير الأصلية حُلّت؟ | `راجع خطة 24`, `review plan 24`, `هل تم بناء الخطة صح؟` |
| `report-cleanup` | يحذف منفذ/مبالغ فيه من تقرير بعد مراجعة الكود | `نظف تقرير X`, `احذف المنفذ من X`, `trim report` |
| `daily-report` | تقرير إنجاز يومي non-technical بـ 8 نقاط (Today/Yesterday) | `اكتب تقرير يومي`, `daily report today/yesterday` |

### 🧑‍💼 Advisory

| Skill | الوظيفة | Trigger |
|-------|---------|---------|
| `tech-advisor` | استشاري تقني Staff+ — توصية محسومة بـ trade-off | `احكم على هذا كود`, `what's the best way to X` |
| `pm-advisor` | استشاري أعمال/منطق Senior PM — Impact×Confidence÷Effort | `ما الأولوية؟`, `اعمل خطة sprint`, `frame this for the client` |

---

## 🔗 سير العمل المقترح

### تطوير يومي (كامل):
```
feature-report → plan-generator → plan-executor → plan-reviewer → commit-message
```

> عند بدء ميزة جديدة من فكرة، ابدأ بـ `feature-report` لتوثيق الميزة (non-technical)، ثم `plan-generator` لتفكيكها لخطط.
(بعد `plan-reviewer` إذا فيه أخطاء، عُد إلى `plan-executor` للتعديل)

### تطوير خفيف:
```
test-case-generator → query-reviewer → commit-message
```

### إصلاح bug:
```
bug-report-formatter → (repro + read code) → bug-resolver → commit-message
```

### قبل release:
```
launch-checklist (Standard variant) → deploy → (smoke test in prod)
```

### تنظيف ربع سنوي:
```
report-cleanup على docs/reports/*.md → report-cleanup مرة أخرى بعد شهر
```

---

## 📁 المجلدات

- **كل skill:** `.opencode/skills/<name>/SKILL.md`
- **Bugs:** `docs/bugs/BUG-YYYY-MM-DD-NNN-<slug>.md` (gitignored)
- **هذا الملف:** `.opencode/SKILLS_INDEX.md` (gitignored)

---

## 📊 إحصائيات

- **عدد skills:** 16
- **آخر تحديث:** 2026-06-26
