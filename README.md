# keyjson-storage

A tiny JSON key-value store over HTTP, built with PHP and MySQL. Each `name` holds one JSON document that anyone can read and only the secret holder can write.

## Structure

| Folder  | Description |
|---------|-------------|
| `db/`   | DB script   |
| `docs/` | wwwroot     |

The API is `docs/index.php`. Full API documentation is in `docs/docs/index.html`, served at `/docs/`.

## Setup

1. Create the database and import the schema (the dump does not create the database itself):

   ```sh
   mysql -u root -p -e "CREATE DATABASE keyjson CHARACTER SET utf8mb4"
   mysql -u root -p keyjson < db/20250804_init.sql
   ```

2. Set the MySQL credentials in `docs/db.php`.

3. Register each name and its write secret in `docs/config.php`:

   ```php
   $config = [
     "tmp" => [
       "secret" => "1234",
     ],
   ];
   ```

4. Serve `docs/` as the web root. Locally:

   ```sh
   php -S localhost:8000 -t docs
   ```

   The API is at `http://localhost:8000/` and the docs page at `http://localhost:8000/docs/`.

## API

| Method | Parameters | Description |
|--------|------------|-------------|
| `GET`  | query `name`, optional `key` | Read the value stored under `name`; with `key`, return only that top-level property (or array index) |
| `POST` | JSON body `{ name, secret, value }` | Create or **fully replace** the value |

Every response has this shape:

```
{ "success": bool, "message": null | string, "data": any /* GET only */ }
```

The HTTP status is always `200`. Check `success` and `message` to see the result:

| `message` | Cause |
|-----------|-------|
| `name not found` | `name` is missing or not in `config.php` |
| `data not found` | GET: the name is registered but nothing has been written yet |
| `key not found` | GET: `key` is given but the value has no such property or index |
| `invalid input` | POST: `value` or `secret` is missing or `null` |
| `invalid secret` | POST: `secret` doesn't match `config.php` |

## jQuery Samples

These use the `tmp` key from `config.php`. Replace `https://your.api/endpoint` with the URL where `docs/` is served.

### 1. Read

Request
```js
$.getJSON('https://your.api/endpoint?name=tmp', resp => console.log(resp))
```

Response
```json
{
    "success": true,
    "message": null,
    "data": [
        {
            "name": "Black",
            "code": "B"
        },
        {
            "name": "White",
            "code": "W"
        }
    ]
}
```

### 2. Read one key

Pass `key` to get only part of the value. This makes the response smaller. For an object, `key` is a property name; for an array, it is an index.

Request
```js
$.getJSON('https://your.api/endpoint?name=tmp&key=0', resp => console.log(resp))
```

Response
```json
{
    "success": true,
    "message": null,
    "data": {
        "name": "Black",
        "code": "B"
    }
}
```

### 3. Write

Request
```js
$.ajax({
    url: 'https://your.api/endpoint',
    type: 'POST',
    contentType: 'application/json',
    data: JSON.stringify({
        name: 'tmp',
        secret: '1234',
        value: [
            { name: 'Yellow', code: 'Y' },
            { name: 'Blue',   code: 'B' },
        ]
    }),
    success: resp => console.log(resp),
});
```

Response
```json
{
    "success": true,
    "message":null
}
```

## Notes

- **Reads are public.** Anyone who knows a name can read its value, so don't store sensitive data.
- **Writes replace the whole value.** To change one item, read the value, modify it, then write it all back.
- Secrets (`docs/config.php`) and DB credentials (`docs/db.php`) are stored in plain PHP files. Change the defaults before deploying.
