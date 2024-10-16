## Batch sources

Hver ressurs har en type: product, contact, event, document, thing.
Hver ressurs har sine egne endepunkt for å legge til, endre og slette
Hver id er unik id hos det integrerende systemet. Vi lager ikke id. Id brukes til å opprette, endre og slette unike ressurser.
Navn på attributter er i hovedsak basert på Schema.org
Alle ressurser kan relateres til alle ressurser. Dvs et produkt kan relateres til en person, event, dokument, artikkel, etc.
Ved hjelp av PATCH endepunkt kan et produkt oppdateres med bare price, salePrice og inStock. Dette er for å unngå å sende hele produktet på nytt.
Vi versjonerer hver endepunkt for seg.
Autentisering: Vi bruker API nøkkel for å autentisere mot API. API nøkkel sendes i headeren som `x-api-key`.
Ingen GET endepunkter, så umulig med datalekkasje via API. Man man se på data i CoreAI admin.

### [POST/PATCH] /api/v1/assistants/{assistantId}/sources/products

```json
[
    {
        "id": "external-product-id-123",
        "priority": 1, //Hvis vi får mer data enn det er plass til i AI contekst så prioriterer vi basert på denne. Dvs sorterer resultatet på prioritet før vi sender det til AI.
        "data": { //Feltnavn hentet fra schema.org og Google Product Feed
            "productNumber": "123",
            "name": "Minigraver Brøyt X 360 grader sving",
            "description": "Minigraver Brøyt X 360 grader sving extra medium long description",
            "gtin": "123",
            "url": "http://example.com/product",
            "priceCurrency": "NOK",
            "price": 123.45,
            "brand": "Brøyt",
            "salePrice": 99.99,
            "inStock": true,
            "longDescription": "Lo rem ipsum dolor sit amet, consectetur adipiscing elit",
            "lastModifiedAt": "2019-01-01T00:00:00Z",
            "properties" : [ //Anbefaler properties fra Schema.org men alt er tillatt 
                {
                    "name": "property1",
                    "value": "value1"
                },
                {
                    "name": "property2",
                    "value": "value2"
                }
            ],
            "images": [ //Første bilde er hovedbilde
                "http://example.com/product1.jpg",
                "http://example.com/product2.png"
            ]
        },
        "contacts": [ //Liste med referanser til personer
            "external-contact-id-122",
            "external-contact-id-123"
        ],
        "events": [ //Liste med referanser til events
            "external-event-id-122",
            "external-event-id-123"
        ],
        "documents": [
            "http://example.com/document1.pdf", //Vi oppdaterer aldri et dokument, forutsetter at det ved oppdatering får en ny URL.
            "http://example.com/document2.pdf"
        ]
        "things": [ //Liste med referanser til things/artikler/websider
            "external-thing-id-122",
            "external-thing-id-123"
        ]
    }
]
```

### [DELETE] /api/v1/assistants/{assistantId}/sources/products

```json
[
    "external-product-id-123",
    "external-product-id-456"
]
```

### [POST] /api/v1/assistants/{assistantId}/sources/events

```json
[
    {
        "id": "external-event-id-123",
        "priority": 1,
        "data": {
            "name": "Core Event",
            "location": "Klinestadmoen 10",
            "url": "http://example.com/event",
            "startDate": "2019-01-01T00:00:00Z",
            "endDate": "2019-01-01T00:00:00Z",
            "lastModifiedAt": "2019-01-01T00:00:00Z"
        },
        "contacts": [ //Liste med referanser til personer
            "external-contact-id-122",
            "external-contact-id-123"
        ],
        "products": [
            "external-product-id-122",
            "external-product-id-123"
        ],
        "documents": [
            "http://example.com/document1.pdf", //Vi oppdaterer aldri et dokument, forutsetter at det ved oppdatering får en ny URL.
            "http://example.com/document2.pdf"
        ]
        "things": [ //Liste med referanser til things/artikler/websider
            "external-thing-id-122",
            "external-thing-id-123"
        ]
    }
]
```

### [DELETE] /api/v1/assistants/{assistantId}/sources/events

```json
[
    "external-event-id-123",
    "external-event-id-456"
]
```

### [POST] /api/v1/assistants/{assistantId}/sources/contacts

```json
[
    {
        "id": "external-person-id-123",
        "priority": 0.5,
        "data": { //Feltnavn hentet fra schema.org
            "name": "John Doe",
            "jobTitle": "Software Developer",
            "url": "http://example.com/person",
            "telephone" : "+47 123 45 678",
            "email" : "ola@nordman.no",
            "workLocation" : "Klinestadmoen 10",
            "description": "Hva med vcard format? vcf Virtual Contact File",
            "image": "http://example.com/person.jpg",
            "lastModifiedAt": "2019-01-01T00:00:00Z"
        },
        "products": [
            "external-product-id-122",
            "external-product-id-123"
        ],
        "documents": [
            "http://example.com/document1.pdf", //Vi oppdaterer aldri et dokument, forutsetter at det ved oppdatering får en ny URL.
            "http://example.com/document2.pdf"
        ]
        "events": [
            "external-event-id-122",
            "external-event-id-123"
        ],
        "things": [ //Liste med referanser til things/artikler/websider
            "external-thing-id-122",
            "external-thing-id-123"
        ]
    }
]
```

### [DELETE] /api/v1/assistants/{assistantId}/sources/contacts

```json
[
    "external-contact-id-123",
    "external-contact-id-456"
]
```

### [POST] /api/v1/assistants/{assistantId}/sources/documents

```json
[
    {
        "id": "external-document-id-123",
        "priority": 0.5,
        "data": {
            "name": "Document 2",
            "description": "Document 2 description",
            "longDescription": "Rådata tekst fra dokumentet som vi gir til AI",
            "url": "http://example.com/document",
            "lastModifiedAt": "2019-01-01T00:00:00Z"
        },
        "contacts": [ //Liste med referanser til personer
            "external-contact-id-122",
            "external-contact-id-123"
        ],
        "products": [ //Liste med referanser til produkter
            "external-product-id-122",
            "external-product-id-123"
        ],
        "events": [ //Liste med referanser til events
            "external-event-id-122",
            "external-event-id-123"
        ],
        "things": [ //Liste med referanser til things/artikler/websider
            "external-thing-id-122",
            "external-thing-id-123"
        ]
    }
]
```
### [POST] /api/v1/assistants/{assistantId}/sources/things
Dette er typisk artikler / tekst / etc.
```json
[
    {
        "id": "external-thing-id-123",
        "priority": 0.5,
        "data": {
            "name": "Artikkel 2",
            "description": "Artikkel 2 description",
            "longDescription": "Lorem ipsum dolor sit amet, consectetur adipiscing elit",
            "url": "http://example.com/thing",
            "lastModifiedAt": "2019-01-01T00:00:00Z"
        },
        "contacts": [ //Liste med referanser til personer
            "external-contact-id-122",
            "external-contact-id-123"
        ],
        "products": [ //Liste med referanser til produkter
            "external-product-id-122",
            "external-product-id-123"
        ],
        "events": [ //Liste med referanser til events
            "external-event-id-122",
            "external-event-id-123"
        ],
        "documents": [
            "http://example.com/document1.pdf", //Vi oppdaterer aldri et dokument, forutsetter at det ved oppdatering får en ny URL.
            "http://example.com/document2.pdf"
        ],
    }
]
```


### [POST] /api/v1/assistants/{assistantId}/sources/things
Dette er typisk artikler / tekst / etc.
```json
[
    {
        "id": "external-thing-id-123",
        "priority": 0.5,
        "data": {
            "name": "Artikkel 2",
            "description": "Artikkel 2 description",
            "longDescription": "Lorem ipsum dolor sit amet, consectetur adipiscing elit",
            "url": "http://example.com/thing",
            "lastModifiedAt": "2019-01-01T00:00:00Z"
        },
        "contacts": [ //Liste med referanser til personer
            "external-contact-id-122",
            "external-contact-id-123"
        ],
        "products": [ //Liste med referanser til produkter
            "external-product-id-122",
            "external-product-id-123"
        ],
        "events": [ //Liste med referanser til events
            "external-event-id-122",
            "external-event-id-123"
        ],
        "documents": [
            "http://example.com/document2.pdf"
        ]
    }
]
```

### [DELETE] /api/v1/assistants/{assistantId}/sources/things

```json
[
    "external-thing-id-123",
    "external-thing-id-456"
]
```

## Single Source

### [PATCH/PUT/DELETE] /api/v1/assistants/{assistantId}/sources/products/{productId}

```json
{
    "priority": 1,
    "data": {
        "productNumber": "123",
        "name": "Minigraver Brøyt X 360 grader sving",
        "description": "Minigraver Brøyt X 360 grader sving extra medium long description",
        "gtin": "123",
        "url": "http://example.com/product",
        "priceCurrency": "NOK",
        "price": 123.45,
        "brand": "Brøyt",
        "salePrice": 99.99,
        "inStock": true,
        "longDescription": "Lo rem ipsum dolor sit amet, consectetur adipiscing elit",
        "lastModifiedAt": "2019-01-01T00:00:00Z",
        "properties" : [
            {
                "name": "property1",
                "value": "value1"
            },
            {
                "name": "property2",
                "value": "value2"
            }
        ],
        "images": [
            "http://example.com/product1.jpg",
            "http://example.com/product2.png"
        ]
    },
    "contacts": [ //Liste med referanser til personer
        "external-contact-id-122",
        "external-contact-id-123"
    ],
    "events": [ //Liste med referanser til events
        "external-event-id-122",
        "external-event-id-123"
    ],
    "documents": [
        "http://example.com/document1.pdf", //Vi oppdaterer aldri et dokument, forutsetter at det ved oppdatering får en ny URL.
        "http://example.com/document2.pdf"
    ],
    "things": [ //Liste med referanser til things/artikler/websider
        "external-thing-id-122",
        "external-thing-id-123"
    ]
}
```

### [PATCH/DELETE] /api/v1/assistants/{assistantId}/sources/events/{eventId}

```json
{
    "priority": 1,
    "data": {
        "name": "Core Event",
        "location": "Klinestadmoen 10",
        "url": "http://example.com/event",
        "startDate": "2019-01-01T00:00:00Z",
        "endDate": "2019-01-01T00:00:00Z",
        "lastModifiedAt": "2019-01-01T00:00:00Z"
    },
    "contacts": [ //Liste med referanser til personer
        "external-contact-id-122",
        "external-contact-id-123"
    ],
    "products": [ //Liste med referanser til produkter
        "external-product-id-122",
        "external-product-id-123"
    ],
    "documents": [
        "http://example.com/document1.pdf", //Vi oppdaterer aldri et dokument, forutsetter at det ved oppdatering får en ny URL.
        "http://example.com/document2.pdf"
    ],
    "things": [ //Liste med referanser til things/artikler/websider
        "external-thing-id-122",
        "external-thing-id-123"
    ]
}
```

### [PATCH/DELETE] /api/v1/assistants/{assistantId}/sources/contacts/{contactId}

```json
{
    "priority": 0.5,
    "data": { //Feltnavn hentet fra schema.org
        "name": "John Doe",
        "jobTitle": "Software Developer",
        "url": "http://example.com/person",
        "telephone" : "+47 123 45 678",
        "email" : "ola@nordman.no",
        "workLocation" : "Klinestadmoen 10",
        "description": "Hva med vcard format? vcf Virtual Contact File",
        "image": "http://example.com/person.jpg",
        "lastModifiedAt": "2019-01-01T00:00:00Z"
    },
    "products": [ //Liste med referanser til produkter
        "external-product-id-122",
        "external-product-id-123"
    ],
    "events": [ //Liste med referanser til events
        "external-event-id-122",
        "external-event-id-123"
    ],
    "documents": [
        "http://example.com/document1.pdf", //Vi oppdaterer aldri et dokument, forutsetter at det ved oppdatering får en ny URL.
        "http://example.com/document2.pdf"
    ],
    "things": [ //Liste med referanser til things/artikler/websider
        "external-thing-id-122",
        "external-thing-id-123"
    ]
}
```

### [PATCH/DELETE] /api/v1/assistants/{assistantId}/sources/documents

```json
{
    "priority": 0.5,
    "data": {
        "name": "Document 2",
        "description": "Document 2 description",
        "longDescription": "Rådata tekst fra dokumentet som vi gir til AI",
        "url": "http://example.com/document",
        "lastModifiedAt": "2019-01-01T00:00:00Z"
    },
    "contacts": [ //Liste med referanser til personer
        "external-contact-id-122",
        "external-contact-id-123"
    ],
    "products": [ //Liste med referanser til produkter
        "external-product-id-122",
        "external-product-id-123"
    ],
    "events": [ //Liste med referanser til events
        "external-event-id-122",
        "external-event-id-123"
    ],
    "things": [ //Liste med referanser til things/artikler/websider
        "external-thing-id-122",
        "external-thing-id-123"
    ]
}
```

### [PATCH/DELETE] /api/v1/assistants/{assistantId}/sources/things/{thingId}

```json
{
    "priority": 0.5,
    "data": {
        "name": "Artikkel 2",
        "description": "Artikkel 2 description",
        "longDescription": "Lorem ipsum dolor sit amet, consectetur adipiscing elit",
        "url": "http://example.com/thing",
        "lastModifiedAt": "2019-01-01T00:00:00Z"
    },
    "contacts": [ //Liste med referanser til personer
        "external-contact-id-122",
        "external-contact-id-123"
    ],
    "products": [ //Liste med referanser til produkter
        "external-product-id-122",
        "external-product-id-123"
    ],
    "events": [ //Liste med referanser til events
        "external-event-id-122",
        "external-event-id-123"
    ],
    "documents": [
        "http://example.com/document1.pdf", //Vi oppdaterer aldri et dokument, forutsetter at det ved oppdatering får en ny URL.
        "http://example.com/document2.pdf"
    ]
}
```
