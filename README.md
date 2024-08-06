## Document API

This Symfony API manage the users and control the authorisation they have about interaction on product. We also includes authentication via JWT to secure access.

### Prerequisites
- PHP >= 8.1
- Composer
- Symfony CLI (optional)
- MySQL (or another database compatible)
- Insomnia ou Postman for the test of API 

### Installation
- Clone a dépôt :

```bash
git clone https://github.com/votre-utilisateur/votre-depot.git
cd votre-depot
```

- Installation of dependence :

```bash
composer install
```

- Generate the keys SSH for JWT :

```bash
php bin/console lexik:jwt:generate-keypair
```

- construct the database :

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

- start the server :

```bash
symfony serve
        or
php bin/console localhost:8000 -t public
```

### Endpoints
#### Authentication
- Login : /api/login
- Méthode : POST
- body : { "username": "user1", "password": "user1" }
- Response : { "token": "your_jwt_token" }

#### Users
Create a users or register : /api/user
- Méthode : POST
- body : { "username": "user1", "email": "user1@localhost.com", "password": "user1" }
- Response : Détails of user created.

Change the role of user : /api/user/{id}
- Méthode : PATCH
- Headers : Authorization: Bearer <your_token>
- body : { "roles": ["ROLE_EDIT"] }
- Response : Détails of user updated.


#### Products
Create a product : /api/product
- Méthode : POST
- Headers : Authorization: Bearer <your_token>
- body : { "name": "product", "price": 99.99, "category": { "id": 2 } }
- Response : Détails of product created.

Update a product : /api/product/{id}
- Méthode : PATCH
- Headers : Authorization: Bearer <your_token>
- body : { "name": "New Name", "price": 89.99 }
- Response : Détails of product updated.

Upload image for a product : /api/products/{id}/image
- Méthode : POST
- Headers : Authorization: Bearer <your_token>
- body : filename of image in the variable filename and the file image has stocked on this path "/public/images/product".
- Response : Détails of image upload and the product associate.

### Roles Management
Default role include are "ROLE_USER and ROLE_SUPER_ADMIN". A user(with have "ROLE_USER") can be assigned an additional role such as "ROLE_EDIT or ROLE_GRANT_EDIT" by the administrator(with have "ROLE_SUPER_ADMIN"), using the role change endpoint.

Example de JSON to test the role change :
```json
{
"roles": ["ROLE_EDIT"]
}
```

### Test of API
You can  use this tolls for the test of API: Insomnia ou Postman to send requests on your API. Make sure to include the JWT token in the Authorization header for request requiring authentication.

### Security
API use JWT to secure endpoints. Make sure that your server uses HTTPS in production to protect JWT tokens and user data.

### API Documentation
you can use this url [localhost:8000/api/doc](http://localhost:8000/api/doc) to visualise the documentation of request generate in the api if you start your server on the port 8000

