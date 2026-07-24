# دليل Frontend: مساعد وصف العقار (AI Description Assistant)

> **الإصدار:** 1.0
> **التاريخ:** 2026-07-24

## في نموذج إنشاء/تعديل العقار

```jsx
// components/PropertyDescriptionAssistant.tsx
import { useState } from 'react';

export function PropertyDescriptionAssistant({ formData, onApply }) {
  const [loading, setLoading] = useState(false);
  const [mode, setMode] = useState(null);
  const [suggestions, setSuggestions] = useState(null);
  const [error, setError] = useState(null);

  const callAI = async (endpoint, body) => {
    setLoading(true);
    setMode(endpoint);
    setError(null);
    try {
      const response = await fetch(`/api/ai/description/${endpoint}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${getAuthToken()}`,
        },
        body: JSON.stringify(body),
      });

      if (response.status === 429) {
        setError('تجاوزت حد الاستخدام. حاول بعد ساعة.');
        return;
      }
      if (response.status === 503) {
        setError('خدمة الذكاء الاصطناعي غير متاحة.');
        return;
      }
      if (response.status === 422) {
        const data = await response.json();
        setError(data.message || 'البيانات المُرسلة غير صحيحة.');
        return;
      }
      if (!response.ok) throw new Error('AI error');

      const data = await response.json();
      setSuggestions(data.data);
    } catch (err) {
      setError('حدث خطأ. حاول مرة أخرى.');
    } finally {
      setLoading(false);
    }
  };

  const handleGenerate = () => {
    callAI('generate', {
      property_type: formData.type,
      rooms: formData.rooms,
      bathrooms: formData.bathrooms,
      area: formData.area,
      city: formData.city,
      price: formData.price,
      features: formData.features,
      language: formData.language ?? 'ar',
    });
  };

  const handleImprove = () => {
    if (!formData.description || formData.description.length < 10) {
      setError('الوصف قصير جدًا. اكتب نصًا أطول أولاً.');
      return;
    }
    callAI('improve', {
      current_description: formData.description,
      language: formData.language ?? 'ar',
    });
  };

  const handleSuggestTitle = () => {
    callAI('suggest-title', {
      property_type: formData.type,
      rooms: formData.rooms,
      city: formData.city,
      language: formData.language ?? 'ar',
    });
  };

  const handleSuggestFeatures = () => {
    callAI('suggest-features', {
      property_type: formData.type,
      language: formData.language ?? 'ar',
    });
  };

  return (
    <div className="ai-assistant">
      <div className="assistant-buttons">
        <button onClick={handleGenerate} disabled={loading}>
          ✨ اقترح وصف
        </button>
        <button onClick={handleImprove} disabled={loading || !formData.description}>
          🔧 حسّن النص
        </button>
        <button onClick={handleSuggestTitle} disabled={loading}>
          💡 اقترح عنوان
        </button>
        <button onClick={handleSuggestFeatures} disabled={loading}>
          ➕ اقترح مميزات
        </button>
      </div>

      {loading && <Spinner />}

      {error && <div className="ai-error">{error}</div>}

      {suggestions && mode === 'generate' && (
        <SuggestionModal
          title="الوصف المقترح"
          content={suggestions.description}
          onApply={(text) => onApply('description', text)}
          onRegenerate={handleGenerate}
          onClose={() => setSuggestions(null)}
        />
      )}

      {suggestions && mode === 'improve' && (
        <SuggestionModal
          title="الوصف المحسّن"
          content={suggestions.description}
          onApply={(text) => onApply('description', text)}
          onRegenerate={handleImprove}
          onClose={() => setSuggestions(null)}
        />
      )}

      {suggestions && mode === 'suggest-title' && (
        <TitleSuggestions
          titles={suggestions.titles}
          onApply={(title) => onApply('name', title)}
          onClose={() => setSuggestions(null)}
        />
      )}

      {suggestions && mode === 'suggest-features' && (
        <FeatureCheckboxes
          features={suggestions.features}
          selected={formData.features ?? []}
          onChange={(features) => onApply('features', features)}
          onClose={() => setSuggestions(null)}
        />
      )}
    </div>
  );
}
```

## Modals

```jsx
function SuggestionModal({ title, content, onApply, onRegenerate, onClose }) {
  return (
    <Modal onClose={onClose}>
      <h3>{title}</h3>
      <textarea readOnly defaultValue={content} rows={10} />
      <div className="modal-actions">
        <button onClick={() => onApply(content)} className="btn-primary">
          تطبيق
        </button>
        <button onClick={onRegenerate}>إعادة التوليد</button>
        <button onClick={onClose}>إلغاء</button>
      </div>
    </Modal>
  );
}

function TitleSuggestions({ titles, onApply, onClose }) {
  return (
    <Modal onClose={onClose}>
      <h3>اقتراحات العنوان</h3>
      <div className="title-suggestions">
        {titles.map((title, i) => (
          <button
            key={i}
            onClick={() => {
              onApply(title);
              onClose();
            }}
            className="title-suggestion"
          >
            {title}
          </button>
        ))}
      </div>
    </Modal>
  );
}

function FeatureCheckboxes({ features, selected, onChange, onClose }) {
  const [local, setLocal] = useState(selected);
  return (
    <Modal onClose={onClose}>
      <h3>الميزات المقترحة</h3>
      <div className="feature-checkboxes">
        {features.map((feature) => (
          <label key={feature}>
            <input
              type="checkbox"
              checked={local.includes(feature)}
              onChange={(e) => {
                const next = e.target.checked
                  ? [...local, feature]
                  : local.filter((f) => f !== feature);
                setLocal(next);
              }}
            />
            {feature}
          </label>
        ))}
      </div>
      <div className="modal-actions">
        <button onClick={() => { onChange(local); onClose(); }} className="btn-primary">
          تطبيق
        </button>
        <button onClick={onClose}>إلغاء</button>
      </div>
    </Modal>
  );
}
```

## Usage في PropertyForm

```jsx
function PropertyForm() {
  const [formData, setFormData] = useState({
    name: '',
    type: 'apartment',
    rooms: 3,
    bathrooms: 2,
    area: 150,
    city: 'Riyadh',
    price: 500000,
    description: '',
    features: [],
  });

  const handleApply = (field, value) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  return (
    <form>
      <input
        value={formData.name}
        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
        placeholder="اسم العقار"
      />

      <PropertyDescriptionAssistant formData={formData} onApply={handleApply} />

      <textarea
        value={formData.description}
        onChange={(e) => setFormData({ ...formData, description: e.target.value })}
        placeholder="وصف العقار"
      />

      {/* other fields... */}
    </form>
  );
}
```

## Acceptance Checklist

- [ ] 4 أزرار في نموذج العقار
- [ ] Modal لكل وضع
- [ ] زر "تطبيق" يحدّث الحقل
- [ ] زر "إعادة التوليد" يستدعي الـ AI مجدداً
- [ ] spinner أثناء التحميل
- [ ] رسائل خطأ واضحة (503, 429, 422)
- [ ] زر "حسّن" معطل إذا الوصف فارغ
- [ ] checkboxes للميزات (متعدد الاختيار)
