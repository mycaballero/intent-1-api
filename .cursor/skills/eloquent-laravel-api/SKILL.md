---
name: eloquent-laravel-api
description: Implementa y revisa endpoints de API Laravel con Eloquent, DTOs (Spatie Data), Form Requests, Services que devuelven Collection y API Resources. Usar al crear o refactorizar listados (index), CRUD por entidad o al seguir el patrón Procedure (index primero, IndexRequest, IndexData, Service→Collection, Resource::collection).
---

# Eloquent y patrón API (Laravel)

Patrón de referencia: **Procedure** (index, store, update). Aplicar modelo por modelo y endpoint por endpoint.

## 1. Eloquent preferido

- **Consultas**: `Model::query()->...->get()` (devuelve `Collection`), no arrays ni `DB::table()`.
- **Relaciones**: `->with(['relation1', 'relation2'])` para eager loading.
- **Filtros condicionales**: `->when($condición, fn (Builder $q) => $q->where(...))`.
- **Filtros por relación**: `->whereHas('relation', fn (Builder $q) => ...)` y `->orWhereHas(...)`.
- **Orden**: `->orderBy($sort_by ?? 'campo_defecto', $sort_direction ?? 'asc')`.
- **Tipos de retorno en Service**: `Collection` para listados; modelo o DTO para un solo ítem cuando aplique.

## 2. Rutas (`routes/api.php`)

- Prefijo `v1`, rutas autenticadas en `middleware('auth:sanctum')`.
- Por recurso: grupo `prefix` (ej. `procedures`). Dentro del grupo:
  1. Rutas **específicas** primero (ej. `GET statuses`).
  2. `GET /` → index.
  3. `POST /` → store.
  4. `PUT {id}` → update.
  5. Otras acciones (ej. attachments, sub-recursos).
- URLs en plural; evitar listas planas sin agrupar.

## 3. Controlador

- **Orden de métodos**: `index` primero, luego `statuses` (si aplica), `store`, `update`, etc.
- **Index**:
  - Type-hint del **Form Request** del listado: `{Entity}IndexRequest`.
  - Respuesta: `response()->json({Entity}Resource::collection($this->service->index({Entity}IndexData::from($request))))`.
- **Inyección**: constructor con `private readonly {Entity}ServiceInterface $service`.
- Solo HTTP: validación vía Form Request, códigos de estado, forma de la respuesta. Sin lógica de negocio.

## 4. Form Request para index

- Ubicación: `app/Http/Requests/{Entity}/{Entity}IndexRequest.php`.
- `authorize(): bool` (ej. `return true`).
- `rules(): array`: solo campos del listado (filtros y orden). Ejemplo:

```php
return [
    'date' => ['nullable', 'date'],
    'search' => ['nullable', 'string'],
    'status' => ['nullable', 'string'],
    'sort_by' => ['nullable', 'string'],
    'sort_direction' => ['nullable', 'string'],
];
```

- Reglas claras y agrupadas; sin duplicar validación que ya haga el Data.

## 5. Data (DTO) para index

- Clase: `App\Data\{Entity}\{Entity}IndexData` (Spatie Laravel Data).
- Propiedades **nullable** que reflejen los query params del index (date, search, status, sort_by, sort_direction).
- Uso en controlador: `{Entity}IndexData::from($request->validatedSnake())` (BaseApiRequest; claves en snake_case para el Data).
- Sin lógica; solo estructura de entrada para el servicio.

## 6. Servicio

- **Contract**: método `index({Entity}IndexData $data): Collection` (o `Illuminate\Database\Eloquent\Collection`).
- **Implementación**: usar Eloquent (`Model::query()->with(...)->when(...)->whereHas(...)->orderBy(...)->get()`).
- Devolver **Collection** (resultado de `->get()`), no convertir a array.
- Contracts en `App\Contracts\{Entity}\`, implementaciones en `App\Services\{Entity}\`.

## 7. API Resource

- **Listado**: `{Entity}Resource::collection($collection)`; el servicio pasa la Collection directamente.
- **Contenido del Resource**: solo los campos que la API necesita.
- **Relaciones**: exponer con **Resources propios** (ej. `PatientResource`, `DoctorResource`), no arrays crudos.
- **Enums/estados**: usar un Resource dedicado (ej. `ProcedureStatusResource`) con `code` y `description` (o lo que defina el enum).
- Ubicación: `app/Http/Resources/{Entity}/{Entity}Resource.php`.

## Checklist por endpoint (index)

Al implementar o revisar un index:

1. Ruta: GET dentro del grupo del recurso; rutas específicas antes del index.
2. Controlador: método `index` primero; recibe `{Entity}IndexRequest`; retorna `response()->json({Entity}Resource::collection($this->service->index({Entity}IndexData::from($request->validatedSnake()))))`.
3. Form Request: `{Entity}IndexRequest` con reglas de filtros/orden.
4. Data: `{Entity}IndexData` con propiedades nullable alineadas al request.
5. Service: `index({Entity}IndexData): Collection`; consulta con Eloquent y `->get()`.
6. Resource: solo campos necesarios; relaciones y enums con sus propios Resources.

## Ejemplo de flujo (Procedure)

- **Route**: `Route::get('/', [ProcedureController::class, 'index']);` (después de `statuses`).
- **Controller**: `index(ProcedureIndexRequest $request)` → `ProcedureResource::collection($this->procedureService->index(ProcedureIndexData::from($request->validatedSnake())))`.
- **ProcedureIndexRequest**: reglas para `date`, `search`, `status`, `sort_by`, `sort_direction`.
- **ProcedureIndexData**: mismas propiedades nullable.
- **ProcedureService::index(ProcedureIndexData)**: `Procedure::query()->with([...])->when(...)->get()` → `Collection`.
- **ProcedureResource**: id, date, code, status (ProcedureStatusResource), patient (PatientResource), procedureDoctors (ProcedureDoctorResource::collection), etc.

Implementar este flujo **modelo por modelo y endpoint por endpoint**, manteniendo Eloquent y tipos (Collection, Data, Resource) en todo el recorrido.
