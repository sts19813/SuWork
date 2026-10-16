# API de cobranza pagada — Airbnb SU → SuHomes

Esta API registra en SuHomes los cobros que Airbnb SU ya confirmó como pagados. Cada llamada exitosa crea tres registros enlazados:

1. Una **cobranza** visible en la propiedad y en el módulo Cobranza de SuHomes.
2. Un **pago validado** por el importe total; la cobranza queda con estado `paid` desde su creación.
3. Un evento de auditoría de Airbnb SU, usado para impedir duplicados.

No crea un inquilino de SuHomes: el huésped se guarda como `customer_name`, porque una estancia de Airbnb no es un contrato de arrendamiento. En la interfaz el cargo muestra la etiqueta **Airbnb SU** y el nombre del cliente.

## Endpoint

`POST https://suhomes.app/api/internal/airbnb/financial-events`

En ambiente de pruebas, sustituir el dominio por el de staging. El endpoint recibe JSON y siempre devuelve JSON.

### Autenticación

Enviar un token Sanctum de la integración con la habilidad `airbnb`.

```http
Authorization: Bearer <SUHOMES_API_TOKEN>
Accept: application/json
Content-Type: application/json
```

El administrador de SuHomes debe emitir el token una sola vez en el servidor de SuHomes:

```bash
php artisan integrations:issue-airbnb-token --rotate
```

El valor mostrado debe guardarse únicamente como secreto de Airbnb SU, por ejemplo `SUHOMES_API_TOKEN`. No se debe registrar en código, repositorios, capturas ni logs.

## Payload

| Campo | Obligatorio | Descripción |
| --- | --- | --- |
| `property_uuid` | Sí | UUID de la propiedad en SuHomes. Se obtiene al enlazar la propiedad mediante la API de propiedades. |
| `external_event_id` | Sí | Identificador inmutable y único del evento en Airbnb SU. Es la llave de idempotencia. |
| `event_type` | Sí | Tipo técnico del evento, por ejemplo `reservation_payment`, `late_checkout`, `extra_charge` o `damage_charge`. |
| `concept` | Sí | Texto que se verá en Cobranza, por ejemplo `Late check-out` o `Cargo adicional de limpieza`. Máximo 190 caracteres. |
| `customer_name` | Sí | Nombre del huésped/cliente que realizó el pago. Máximo 190 caracteres. |
| `amount` | Sí | Importe positivo con hasta dos decimales. |
| `payment_method` | Sí | Uno de: `spei`, `cash`, `card`, `transfer`, `bank_deposit`, `other`. |
| `paid_at` | No | Fecha u hora de pago ISO-8601. Si se omite, SuHomes usa la hora de recepción. |
| `currency` | No | Moneda ISO de tres letras. Predeterminada: `MXN`. |
| `external_reservation_id` | No | ID de reservación de Airbnb SU para trazabilidad. |
| `payload` | No | Objeto JSON adicional para auditoría. No enviar credenciales ni datos de tarjeta. |

La propiedad debe estar habilitada y **enlazada** con Airbnb SU. Una propiedad sin enlace recibe `422`; esto evita que un evento de Airbnb se asigne a una propiedad equivocada.

## Ejemplo: pago de reservación

```bash
curl --request POST 'https://suhomes.app/api/internal/airbnb/financial-events' \
  --header 'Authorization: Bearer <SUHOMES_API_TOKEN>' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
    "property_uuid": "aa9d78ce-3543-43b1-a299-256e07d33279",
    "external_event_id": "payment_res_8472_20261016",
    "event_type": "reservation_payment",
    "external_reservation_id": "RES-8472",
    "concept": "Pago de reservación",
    "customer_name": "Ana García",
    "amount": 4250.00,
    "currency": "MXN",
    "payment_method": "card",
    "paid_at": "2026-10-16T10:42:11-06:00",
    "payload": {
      "reservation_code": "RES-8472"
    }
  }'
```

## Ejemplo: late check-out

Un late check-out es un cargo adicional, no una renta. Se envía con su propio `external_event_id` y queda pagado igualmente.

```json
{
  "property_uuid": "aa9d78ce-3543-43b1-a299-256e07d33279",
  "external_event_id": "late_checkout_RES-8472_01",
  "event_type": "late_checkout",
  "external_reservation_id": "RES-8472",
  "concept": "Late check-out",
  "customer_name": "Ana García",
  "amount": 650.00,
  "payment_method": "card",
  "paid_at": "2026-10-18T13:05:00-06:00"
}
```

## Respuesta exitosa nueva (`201`)

```json
{
  "data": {
    "id": 31,
    "external_event_id": "late_checkout_RES-8472_01",
    "created": true,
    "status": "paid",
    "charge": {
      "uuid": "6d363a02-8f63-4bbc-9d4d-3fd45c77c772",
      "status": "paid",
      "source": "airbnbsu"
    },
    "payment": {
      "id": 57,
      "status": "succeeded"
    }
  }
}
```

La cobranza se crea con `paid_amount = amount` y el pago con `status = succeeded`; por ello aparece como **Pagado** sin requerir validación manual en SuHomes.

## Idempotencia y reintentos

Airbnb SU debe conservar y reutilizar el mismo `external_event_id` cuando reintente una solicitud por timeout, error de red o respuesta no recibida.

- Primera recepción: `201`, `created: true`.
- Reintento del mismo evento y propiedad: `200`, `created: false`; no crea otra cobranza ni otro pago.
- Reutilizar el mismo `external_event_id` para otra propiedad: `409`.

No cambies un evento ya enviado. Para una corrección o un nuevo concepto debe enviarse un nuevo `external_event_id`; así el historial financiero permanece auditable.

## Respuestas de error

| Código | Motivo | Acción recomendada en Airbnb SU |
| --- | --- | --- |
| `401` | Falta token o es inválido. | Revisar el secreto configurado. |
| `403` | El token no tiene la habilidad `airbnb`. | Emitir token de integración correcto. |
| `409` | El ID externo ya pertenece a otra propiedad. | No reusar IDs de evento entre propiedades; revisar el mapeo. |
| `422` | Datos inválidos o propiedad no enlazada. | Corregir el payload o enlazar la propiedad antes de reintentar. |
| `500`/`503` | Error temporal de SuHomes. | Reintentar con el mismo `external_event_id` usando backoff. |

Los errores de validación incluyen `message` y `errors` con el detalle por campo. Ejemplo de propiedad no enlazada:

```json
{
  "message": "La propiedad no está enlazada con Airbnb SU."
}
```

## Reglas operativas para Airbnb SU

- Enviar solo pagos confirmados. SuHomes los considera liquidados inmediatamente.
- Enviar un evento por cada cobro: pago de estancia, late check-out, limpieza, daño u otro adicional.
- Usar `concept` para el texto entendible por operación; `event_type` para la clasificación técnica.
- Nunca enviar el número de tarjeta, CVV, tokens de procesador ni credenciales dentro de `payload`.
- Tratar cualquier respuesta `2xx` como procesada. Ante duda por timeout, reintentar con el mismo `external_event_id`.

## Despliegue en SuHomes

Después de desplegar esta versión, ejecutar:

```bash
php artisan migrate --force
php artisan integrations:issue-airbnb-token --rotate
```

El segundo comando invalida los tokens anteriores llamados `airbnb-su`; coordinar el cambio del secreto en Airbnb SU para evitar interrupciones.
