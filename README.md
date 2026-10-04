# Tickets IA

![Tests](https://github.com/JoseBenitezAlba/tickets-ia/actions/workflows/tests.yml/badge.svg)
![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)

Asistente de soporte con IA hecho con **Laravel 13** y **Groq**. Recibe tickets de clientes y, con un endpoint, la IA devuelve un resumen, la categoría, la prioridad y un borrador de respuesta.

## Cómo funciona

1. `POST /api/tickets` guarda el ticket.
2. `POST /api/tickets/{id}/analyze` envía el texto a Groq (modelo configurable, por defecto `llama-3.3-70b-versatile`) pidiendo un JSON.
3. La respuesta se valida: si la IA devuelve una categoría o prioridad fuera de la lista, se usa un valor seguro (`otro` / `media`). Si la IA falla o no devuelve JSON, la API responde 502 y no guarda nada.

Categorías: `facturacion`, `tecnico`, `cuenta`, `envio`, `otro`. Prioridades: `baja`, `media`, `alta`.

## Puesta en marcha

Requisitos: PHP 8.3 o superior, Composer y SQLite.

```bash
git clone https://github.com/JoseBenitezAlba/tickets-ia.git
cd tickets-ia
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
# pon tu clave en .env: GROQ_API_KEY=...
php artisan serve
```

## Endpoints

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/api/tickets` | Lista paginada. Filtros: `category`, `priority` |
| POST | `/api/tickets` | Crea ticket (`subject`, `body`, `customer_email` opcional) |
| GET | `/api/tickets/{id}` | Ver ticket |
| POST | `/api/tickets/{id}/analyze` | Analiza con IA y guarda el resultado (10 por minuto) |

```bash
curl -s -X POST http://127.0.0.1:8000/api/tickets \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"subject":"Cobro duplicado","body":"Me han cobrado dos veces la factura de septiembre."}'

curl -s -X POST http://127.0.0.1:8000/api/tickets/1/analyze -H 'Accept: application/json'
```

## Tests

```bash
php artisan test
```

9 tests de feature. Groq se simula con `Http::fake()`, así que los tests no necesitan clave ni red. Cubren el caso correcto, valores inválidos de la IA, error del proveedor, respuesta que no es JSON y falta de clave.

## Autor

José Manuel Benítez Alba, desarrollador web junior (PHP/Laravel), Cádiz. [GitHub](https://github.com/JoseBenitezAlba)
