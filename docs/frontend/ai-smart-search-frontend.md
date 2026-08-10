# دليل Frontend: البحث الذكي (AI Smart Search)

> **الإصدار:** 1.0
> **التاريخ:** 2026-07-24

## مكون SmartSearchBar

```jsx
// components/SmartSearchBar.tsx
import { useState } from 'react';

export function SmartSearchBar({ onResults }) {
  const [query, setQuery] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const handleSearch = async () => {
    setError(null);
    if (query.length < 5) {
      setError('الرجاء كتابة 5 أحرف على الأقل');
      return;
    }
    if (query.length > 500) {
      setError('الرجاء اختصار النص إلى 500 حرف');
      return;
    }

    setLoading(true);
    try {
      const response = await fetch('/api/ai/search', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ query }),
      });

      if (response.status === 429) {
        setError('تجاوزت حد البحث الذكي. حاول بعد ساعة.');
        return;
      }
      if (!response.ok) throw new Error('Search failed');

      const data = await response.json();
      onResults(data.data);
    } catch (err) {
      setError('حدث خطأ. يرجى المحاولة مرة أخرى.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="smart-search-bar">
      <input
        type="text"
        value={query}
        onChange={(e) => setQuery(e.target.value)}
        placeholder="صف ما تبحث عنه... (مثال: شقة 3 غرف في الرياض)"
        onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
        className="search-input"
      />
      <button onClick={handleSearch} disabled={loading} className="ai-button">
        {loading ? '...' : '🔍 بحث ذكي'}
      </button>
      {error && <div className="error">{error}</div>}
    </div>
  );
}
```

## شاشة النتائج

```jsx
// pages/AiSearchResults.tsx
import { useState } from 'react';
import Link from 'next/link';

export function AiSearchResults({ results }) {
  return (
    <div className="ai-search-results">
      {/* Transparency Panel */}
      {results.extracted_requirements && (
        <div className="what-we-searched">
          <h3>ما الذي بحثت عنه:</h3>
          <ul>
            {results.extracted_requirements.property_type && (
              <li>نوع: {results.extracted_requirements.property_type}</li>
            )}
            {results.extracted_requirements.rooms_min && (
              <li>غرف: {results.extracted_requirements.rooms_min}+</li>
            )}
            {results.extracted_requirements.city && (
              <li>المدينة: {results.extracted_requirements.city}</li>
            )}
            {results.extracted_requirements.price_max && (
              <li>السعر: حتى {results.extracted_requirements.price_max}</li>
            )}
            {results.extracted_requirements.features?.length > 0 && (
              <li>ميزات: {results.extracted_requirements.features.join('، ')}</li>
            )}
          </ul>
        </div>
      )}

      {results.fallback && (
        <div className="fallback-notice">
          ℹ️ البحث الذكي غير متاح. تم استخدام البحث التقليدي.
        </div>
      )}

      <h2>{results.total_matching} عقار مطابق</h2>

      <div className="properties-grid">
        {results.properties.map((p) => (
          <PropertyCard key={p.id} property={p}>
            {p.match_score !== undefined && (
              <span className="match-score">{p.match_score}% تطابق</span>
            )}
          </PropertyCard>
        ))}
      </div>

      {results.properties.length === 0 && (
        <div className="empty-state">
          <h3>لم نجد عقارات مطابقة</h3>
          <Link href="/properties">تصفح كل العقارات</Link>
        </div>
      )}
    </div>
  );
}
```

## Acceptance Checklist

- [ ] شريط بحث في الهيدر مع زر "بحث ذكي"
- [ ] Validation: 5-500 حرف
- [ ] Loading state أثناء الانتظار
- [ ] شاشة نتائج مع شفافية (transparency)
- [ ] Match score % لكل عقار
- [ ] Empty state عند 0 نتائج
- [ ] Fallback message عند فشل API
- [ ] Rate limit message عند 429
