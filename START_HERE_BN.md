# নতুন version চালানোর নিয়ম

পুরোনো database এবং account রাখতেই পারবেন। পুরোনো `ccp-api/.env` ও `ccp-api/storage/app` নতুন folder-এ copy করুন। APP_KEY বদলাবেন না। Database backup রাখুন।

VS Code Git Bash-এ **নতুন career-counselling-placement folder থেকে**:

```bash
cd ccp-api
composer install
php.exe ../scripts/prepare-env.php
php.exe artisan config:clear
php.exe artisan route:clear
php.exe artisan migrate
cd ../ccp-web
npm ci
cp -n .env.example .env
```

`migrate:fresh` দেবেন না; পুরোনো database-এ demo SQL import/seed দেবেন না। আপনার actual `.env`-এ DB_PORT=3307, DB_DATABASE=career_counselling_placement এবং আগের সঠিক username/password থাকলে সেগুলোই রাখুন।

সব একসঙ্গে চালাতে project root থেকে:

```bash
MSYS_NO_PATHCONV=1 cmd.exe /c 'scripts\start.bat'
```

অথবা চারটি VS Code terminal:

1. backend: `cd ccp-api` → `php.exe artisan serve --host=localhost --port=8000`
2. frontend: `cd ccp-web` → `npm run dev -- --host localhost --port 5173`
3. mail queue: `cd ccp-api` → `php.exe artisan queue:work --tries=3`
4. reminders: `cd ccp-api` → `php.exe artisan schedule:work`

Browser: **http://localhost:5173**। XAMPP MySQL চালু রাখুন। বন্ধ করতে terminal-গুলোতে Ctrl+C দিন।

## নতুন সুবিধা

- Sign in-এর Workspace থেকে চারটি role বেছে **Create … account** করা যাবে। Register screen-এও role বদলানো যাবে।
- Student সরাসরি dashboard পাবে। Career Advisor, Recruiter ও Admin account তৈরি করে sign in করতে পারবে, কিন্তু workspace access-এর আগে existing Admin-এর approval লাগবে।
- Admin: **Users → Account status: Pending approval → Approve account**। Recruiter-এর company-ও **Companies** থেকে verify করুন।
- Admin **Settings** থেকে public page-এর লেখা, FAQ, contact/policy, section দেখানো/লুকানো ও order, announcement, university/site name, logo এবং theme colors বদলাতে পারবেন। How it works/Opportunities আলাদা view-তেই থাকবে; চাইলে home page-এও দেখানো যাবে। পরিবর্তনগুলো audit log-এ থাকবে; user/workflow record স্থায়ীভাবে delete করার control নেই।
- **My profile → Profile image → Choose file → Upload image**। নিজের ছবি ডানদিকে উপরে নামের পাশে দেখাবে।
- Admin sidebar-এ Reports, Audit log, Settings। সবার sidebar-এ Notifications এবং header-এ bell।
- Gmail verification আগের মতোই বন্ধ। MAIL_MAILER=log হলে mail log file-এ যায়, Gmail-এ নয়।

একদম নতুন installation হলে আগে database তৈরি করে `scripts/setup.bat` চালান। Fresh demo Admin: `admin@demo.test` / `DemoPass123!`। পুরোনো installation-এ নিজের existing Admin দিয়ে ঢুকুন।
