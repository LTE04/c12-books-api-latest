SCSM2223 Chapter 11 - Books API with JWT + Vue frontend

Project folder name:
  books-api-jwt

Backend setup:
  cd C:\laragon\www\books-api-jwt
  composer install
  composer dump-autoload
  mysql -u root < sql\schema.sql
  php -S localhost:8000 -t public

Frontend setup in a second terminal:
  cd C:\laragon\www\books-api-jwt\frontend
  npm install
  npm run dev

Open:
  http://localhost:5173/

Seeded users:
  admin@books.test  / password  (admin, can delete)
  member@books.test / password  (member, can create and edit)

API summary:
  Public:
    GET  /
    POST /auth/register
    POST /auth/login
    GET  /api/books
    GET  /api/books/{id}

  JWT protected:
    GET    /auth/me
    POST   /api/books
    PUT    /api/books/{id}
    DELETE /api/books/{id}   admin only
