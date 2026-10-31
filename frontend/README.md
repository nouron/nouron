# Nouron Frontend (SvelteKit, ADR 0006)

Spike stage 0: SPA (adapter-static) talking to the Laravel JSON API via Sanctum bearer token.

```
source ~/.nvm/nvm.sh   # Node 22
npm install
VITE_API_URL=http://localhost:8000/api/v1 npm run dev   # default API URL if unset
```

Laravel side: `php artisan migrate` (adds `personal_access_tokens`), CORS origin via `FRONTEND_URL` (default `http://localhost:5173`).
Login with e-mail + password (testdata: `bart@nouron.de` / `test123`).
