---
name: scramble-api-docs
description: Ensures Laravel API endpoints in this project are documented correctly in Scramble, using Form Requests plus DTOs so that POST and PUT request bodies appear accurately in /docs/api.
---

# Scramble API Docs for Somnivet

## Cuándo usar esta skill

Usa esta skill siempre que:

- Se creen o modifiquen **endpoints de API** (especialmente POST/PUT/PATCH).
- El usuario pregunte por **/docs/api**, **Scramble**, o por qué **no aparece el body** de una operación.
- Se añadan nuevos **Form Requests** o **DTOs (Spatie Data)** relacionados con endpoints.

## Contexto del proyecto

- La documentación de la API se sirve con **Scramble** en la ruta `/docs/api`.
- El acceso está controlado por el middleware `RestrictedDocsAccess`.
- Se sigue un patrón híbrido:
  - **Form Requests**: validan la request y definen el esquema del body que Scramble usa para documentar.
  - **DTOs Spatie Laravel Data** (`app/Data/...`): reciben `$request->validated()` y se usan dentro de servicios.
  - Controladores definidos según `.cursor/rules/controllers.mdc`.

## Archivos relevantes de Scramble

Cuando debas entender o tocar algo de Scramble en este proyecto, revisa estos archivos:

- `config/scramble.php`
- `app/Providers/ScrambleServiceProvider.php`
- `app/Http/Middleware/RestrictedDocsAccess.php`
- Rutas de docs (normalmente declaradas en `routes/api.php` o en un provider).

Lee estos archivos con la herramienta de lectura antes de cambiar su comportamiento.

## Patrón para endpoints POST/PUT

Para **cada nuevo endpoint de escritura** (crear/actualizar):

1. **Crear un Form Request**
   - Ubicación: `app/Http/Requests/{Entity}/`
   - Nombre recomendado:
     - `XxxStoreRequest` para POST.
     - `XxxUpdateRequest` para PUT/PATCH.
   - Dentro de `rules()`:
     - Define **todas** las claves del body que deberían aparecer en la doc de Scramble.
     - Usa reglas estándar de Laravel (`required`, `array`, `integer`, `exists`, `string`, `date`, etc.).
     - Para estructuras anidadas, usa notación de puntos:
       - Ejemplo: `patient.hospital_id`, `procedure_types.*.id`, `procedure_types.*.description`.

2. **Usar validación condicional cuando haya varias vías de entrada**
   - Si la lógica de negocio permite más de una manera de enviar información, refleja eso en las reglas:
   - Ejemplo de este proyecto (patient para Procedure):

```php
'patient_id' => [
    'nullable',
    'integer',
    Rule::when(!$this->filled('patient'), ['exists:patients,id']),
],
'patient' => ['nullable', 'array'],
// ...
```

- Interpreta así:
  - Si **no** viene `patient`, cualquier `patient_id` enviado debe existir.
  - Si **sí** viene `patient`, se permite que `patient_id` no exista (la lógica del servicio puede crear al paciente).

3. **Actualizar el controlador**
   - Type-hint del Form Request en los métodos de escritura:

```php
public function store(ProcedureStoreRequest $request): JsonResponse
{
    $data = ProcedureStoreData::from($request->validated());
    $item = $this->procedureService->store($data);

    return response()->json(new ProcedureResource($item), 201);
}
```

- Para update:

```php
public function update(ProcedureUpdateRequest $request, int $id): JsonResponse
{
    $data = ProcedureUpdateData::from($request->validated());
    $updated = $this->procedureService->update($id, $data);

    if (! $updated) {
        return response()->json(['message' => __('Not found')], 404);
    }

    return response()->json(null, 204);
}
```

4. **Crear/ajustar el DTO (Spatie Data) si hace falta**
   - Ubicación típica: `app/Data/{Entity}/`.
   - Ejemplos del proyecto: `ProcedureStoreData`, `ProcedureUpdateData`, `PatientStoreData`.
   - Asegúrate de que el DTO:
     - Tiene la firma `from(mixed ...$payloads): static` si ya está así en el proyecto.
     - Recibe el array validado y hace cualquier mapeo necesario (p. ej. convertir arrays anidados en otros Data).

5. **Alinear con las rules del proyecto**
   - Verifica `.cursor/rules/controllers.mdc` y `.cursor/rules/dto.mdc` para mantener:
     - Form Request → `validated()` → DTO → Servicio.
     - Uso de Resources para formatear la respuesta.

## Cuando el body no aparece bien en /docs/api

Si un endpoint POST/PUT no muestra correctamente el body en Scramble:

1. **Comprueba si existe Form Request**
   - ¿El método del controlador usa algo tipo `XxxStoreRequest` / `XxxUpdateRequest`?
   - Si no, créalo y úsalo en la firma del método.

2. **Revisa `rules()`**
   - Asegúrate de que:
     - Todas las claves esperadas del body estén presentes.
     - Las estructuras anidadas estén definidas con notación de puntos.
     - Los arrays tengan reglas para los elementos (`field.*.id`, `field.*.description`, etc.).

3. **Verifica tipos y opcionalidad**
   - Si un campo está ausente en `rules()`, Scramble no lo documentará.
   - Usa `required`, `required_with`, `nullable`, etc. según la lógica.

4. **Mantén coherencia con DTOs**
   - El Form Request debe describir el mismo shape que el DTO espera tras `validated()`.
   - Si cambias campos en el DTO, actualiza también el Form Request (y viceversa).

## Ejemplos del proyecto

Cuando necesites ejemplos concretos, revisa:

- `app/Http/Requests/Procedure/ProcedureStoreRequest.php`
- `app/Http/Requests/Procedure/ProcedureUpdateRequest.php`
- `app/Http/Requests/Patient/PatientStoreRequest.php`
- Sus controladores asociados:
  - `app/Http/Controllers/Procedure/ProcedureController.php`
  - `app/Http/Controllers/Patient/PatientController.php`

Observa cómo:

- Las reglas de los Form Requests describen el body que Scramble muestra.
- Los controladores usan `$request->validated()` para instanciar los Data.
- La validación condicional (`Rule::when`) refleja reglas de negocio como `patient_id` + `patient`.

## Buenas prácticas específicas para Scramble en este proyecto

- **Mantener los Form Requests actualizados** cuando cambien los DTOs o la lógica de negocio.
- **Agrupar las reglas** de forma legible, por bloques de datos (paciente, relaciones, campos clínicos, etc.).
- **No duplicar lógica de negocio** en el Form Request; solo validación de datos básicos.
- Ante dudas entre validar en Form Request o en servicio:
  - Datos puramente estructurales / tipos / requeridos → Form Request.
  - Reglas complejas de negocio (combinaciones de campos, estados, existencia con side-effects) → servicio + excepciones manejadas en controlador.

