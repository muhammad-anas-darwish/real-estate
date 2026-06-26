# المهمة #13: ملاحظات العملاء المحتملين (Lead Notes)

| الحقل | القيمة |
|---|---|
| **الحالة** | ✅ مكتمل |
| **الأولوية** | 🟡 متوسطة |
| **الجهد المقدّر** | 5–7 ساعات |
| **الاعتمادية** | يجب أن يسبقها #12 (لـ Lead entity) |
| **راجع** | `docs/ideas/mini-crm/report.md` (السيناريو 3؛ القواعد 9، 12–14) |

---

## الوضع الحالي

لا يوجد نظام ملاحظات في النظام. التقرير يفرض قيدًا تجاريًا فريدًا: **قابل للتعديل خلال 24 ساعة فقط، قابل للحذف بواسطة المنشئ فقط** (القواعد 12 و 14، السطور 80 و 82).

---

## مراحل التنفيذ

### المرحلة 1 — Migration و Entity (1 ساعة) ✅

**الملفات:**
- `Modules/Crm/Database/Migrations/2026_06_28_000001_create_lead_notes_table.php`:
  ```php
  Schema::create('lead_notes', function (Blueprint $table) {
      $table->id();
      $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
      $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
      $table->text('body');
      $table->boolean('is_locked')->default(false); // true بعد 24 ساعة
      $table->timestamps();
      $table->softDeletes();

      $table->index(['lead_id', 'created_at']);
  });
  ```
- `Modules/Crm/Entities/LeadNote.php`:
  ```php
  <?php
  namespace Modules\Crm\Entities;

  use App\Models\BaseModel;
  use Illuminate\Database\Eloquent\Relations\BelongsTo;
  use Illuminate\Database\Eloquent\SoftDeletes;
  use Modules\Auth\Entities\User;

  class LeadNote extends BaseModel
  {
      use SoftDeletes;

      protected $fillable = ['lead_id', 'author_id', 'body', 'is_locked'];

      protected $casts = [
          'is_locked' => 'boolean',
      ];

      public function lead(): BelongsTo
      {
          return $this->belongsTo(Lead::class);
      }

      public function author(): BelongsTo
      {
          return $this->belongsTo(User::class, 'author_id');
      }

      public function isEditableBy(User $user): bool
      {
          if ($this->author_id !== $user->id) {
              return false;
          }
          return $this->created_at->gt(now()->subHours(24));
      }

      public function isDeletableBy(User $user): bool
      {
          return $this->author_id === $user->id;
      }
  }
  ```

---

### المرحلة 2 — DTO و Service (1.5 ساعة) ✅

**الملفات:**
- `Modules/Crm/DTOs/LeadNoteDTO.php`:
  ```php
  readonly final class LeadNoteDTO implements DTOInterface
  {
      public function __construct(
          public int $lead_id,
          public int $author_id,
          public string $body,
      ) {}

      public static function fromRequest(array $data): self
      {
          return new self(
              lead_id: $data['lead_id'],
              author_id: auth()->id(),
              body: $data['body'],
          );
      }

      public function toArray(): array
      {
          return [
              'lead_id' => $this->lead_id,
              'author_id' => $this->author_id,
              'body' => $this->body,
          ];
      }
  }
  ```
- `Modules/Crm/Services/LeadNoteService.php`:
  ```php
  class LeadNoteService extends BaseService
  {
      protected const CACHE_TAG = 'crm_lead_notes';

      public function listForLead(Lead $lead): Collection
      {
          abort_unless($lead->isOwnedBy(auth()->id()), 403);
          return $lead->notes()
              ->with('author:id,name')
              ->orderByDesc('created_at')
              ->get();
      }

      public function store(Lead $lead, LeadNoteDTO $dto): LeadNote
      {
          abort_unless($lead->isOwnedBy(auth()->id()), 403);
          if (trim($dto->body) === '') {
              throw new \InvalidArgumentException('note_body_empty');
          }
          $note = DB::transaction(function () use ($lead, $dto) {
              $note = $lead->notes()->create($dto->toArray());
              $lead->touchActivity();
              return $note;
          });
          $this->clearCache();
          return $note;
      }

      public function update(LeadNote $note, string $body): LeadNote
      {
          if (! $note->isEditableBy(auth()->user())) {
              throw new \RuntimeException('note_locked_for_editing');
          }
          if (trim($body) === '') {
          throw new \InvalidArgumentException('note_body_empty');
          }
          DB::transaction(function () use ($note, $body) {
              $note->update(['body' => $body, 'is_locked' => $note->created_at->lte(now()->subHours(24))]);
              $note->lead->touchActivity();
          });
          $this->clearCache();
          return $note->fresh();
      }

      public function delete(LeadNote $note): void
      {
          if (! $note->isDeletableBy(auth()->user())) {
              throw new \RuntimeException('note_delete_unauthorized');
          }
          DB::transaction(function () use ($note) {
              $note->delete();
              $note->lead->touchActivity();
          });
          $this->clearCache();
      }
  }
  ```

---

### المرحلة 3 — FormRequests و Resource و Policy (1 ساعة) ✅

**الملفات:**
- `Modules/Crm/Http/Requests/StoreLeadNoteRequest.php`:
  ```php
  public function rules(): array
  {
      return [
          'body' => ['required', 'string', 'min:1', 'max:5000'],
      ];
  }
  ```
- `Modules/Crm/Http/Requests/UpdateLeadNoteRequest.php` — نفس القواعد مع `sometimes`.
- `Modules/Crm/Http/Resources/LeadNoteResource.php`:
  ```php
  public function getCustomData(): array
  {
      return [
          'id' => $this->id,
          'lead_id' => $this->lead_id,
          'body' => $this->body,
          'is_locked' => $this->is_locked,
          'is_editable' => $this->isEditableBy(auth()->user()),
          'is_deletable' => $this->isDeletableBy(auth()->user()),
          'created_at' => $this->created_at->format('Y-m-d H:i:s'),
          'author' => [
              'id' => $this->author?->id,
              'name' => $this->author?->name,
          ],
      ];
  }

  public function getRelationMap(): array
  {
      return ['author' => \Modules\Auth\Http\Resources\UserResource::class];
  }
  ```
- `Modules/Crm/Policies/LeadNotePolicy.php`:
  ```php
  public function update(User $user, LeadNote $note): bool
  {
      return $note->isEditableBy($user);
  }
  public function delete(User $user, LeadNote $note): bool
  {
      return $note->isDeletableBy($user);
  }
  ```

---

### المرحلة 4 — Controller و Routes (1 ساعة) ✅

**الملفات:**
- `Modules/Crm/Http/Controllers/LeadNoteController.php`:
  ```php
  public function index(int $leadId)
  {
      $lead = $this->leadService->find($leadId);
      $notes = $this->noteService->listForLead($lead);
      return $this->successResponse(LeadNoteResource::collection($notes));
  }

  public function store(StoreLeadNoteRequest $request, int $leadId)
  {
      $lead = $this->leadService->find($leadId);
      $dto = LeadNoteDTO::fromRequest($request->validated() + ['lead_id' => $lead->id]);
      $note = $this->noteService->store($lead, $dto);
      return $this->successResponse(new LeadNoteResource($note))->created('lead_note');
  }

  public function update(UpdateLeadNoteRequest $request, int $noteId)
  {
      $note = LeadNote::findOrFail($noteId);
      $updated = $this->noteService->update($note, $request->validated()['body']);
      return $this->successResponse(new LeadNoteResource($updated))->updated('lead_note');
  }

  public function destroy(int $noteId)
  {
      $note = LeadNote::findOrFail($noteId);
      $this->noteService->delete($note);
      return $this->successResponse(null)->deleted('lead_note');
  }
  ```
- تحديث `Modules/Crm/Routes/api.php`:
  ```php
  Route::get('leads/{leadId}/notes', [LeadNoteController::class, 'index'])->name('api.crm.leads.notes.index');
  Route::post('leads/{leadId}/notes', [LeadNoteController::class, 'store'])->name('api.crm.leads.notes.store');
  Route::patch('notes/{noteId}', [LeadNoteController::class, 'update'])->name('api.crm.notes.update');
  Route::delete('notes/{noteId}', [LeadNoteController::class, 'destroy'])->name('api.crm.notes.destroy');
  ```

---

### المرحلة 5 — اختبارات (1.5 ساعة)
**الملف:** `Modules/Crm/Tests/LeadNoteTest.php`:
1. إنشاء ملاحظة بحقول صحيحة → 201.
2. إنشاء ملاحظة بـ body فارغ → 422.
3. عرض ملاحظات lead محدد → 200 مع author مضمّن.
4. تعديل ملاحظة من إنشاء المستخدم خلال 24 ساعة → 200.
5. تعديل ملاحظة من إنشاء مستخدم آخر → 403.
6. تعديل ملاحظة عمرها 25 ساعة → 422.
7. حذف ملاحظة من المنشئ → 200.
8. حذف ملاحظة من مستخدم آخر → 403.
9. تاجر آخر يحاول الوصول → 403.
10. `last_activity_at` للـ lead يُحدّث عند الإضافة/التعديل/الحذف.

---

## المهام التي يمكن تنفيذها بالتوازي

| المرحلة | لماذا يمكن تشغيلها بالتوازي | تعتمد على |
|---------|------------------------------|-----------|
| 1 + 2 | الـ Service يحتاج entity فقط للمرحلة النهائية | يعتمد على 1 |
| 3 + 4 | الـ Controller يستهلك كل ما سبق | يعتمد على 2 و 3 |
| 5 | بعد 4 | يعتمد على 4 |

---

## معايير القبول

- [x] `php artisan migrate` ينشئ جدول `lead_notes` مع كل indexes.
- [x] `php artisan test --testsuite=Modules --filter=LeadNoteTest` ينجح بكل السيناريوهات الـ 10.
- [x] ملاحظة عمرها 25 ساعة لا يمكن تعديلها حتى لو كان المنشئ.
- [x] مستخدم آخر (حتى تاجر آخر) لا يستطيع تعديل ملاحظة.
- [x] المنشئ فقط يستطيع الحذف.
- [x] `is_editable` و `is_deletable` تُحسب بشكل صحيح في الـ Resource.
- [x] body فارغ يرفض الحفظ بـ 422.
- [x] ترتيب الملاحظات من الأحدث للأقدم.

---

> **⚠️ تعليمات الصيانة:** يجب تحديث هذا الملف (تغيير ✅ / ❌) فور إكمال كل مرحلة من مراحل الخطة، وتحديث `docs/plans/README.md` ليعكس الحالة الجديدة.
