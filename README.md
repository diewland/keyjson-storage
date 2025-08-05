# keyjson-storage

## jQuery Samples

### 1. Read

Request
```js
$.getJSON('https://your.api/endpoint?name=sample', resp => console.log(resp))
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

### 2. Write

Request
```js
$.ajax({
    url: 'https://your.api/endpoint',
    type: 'POST',
    contentType: 'application/json',
    data: JSON.stringify({
        name: 'sample',
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
