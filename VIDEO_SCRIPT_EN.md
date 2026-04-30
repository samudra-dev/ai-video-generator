# Video Walkthrough Script — AI Video Generator (English)
**Target Duration:** 3–5 minutes
**Language:** English

---

## Scene 1: Intro (~20 seconds)
**[Show: Login page in browser]**

> "Hi, my name is Samudra Zulqifli. In this video, I'll walk you through the AI Video Generator project I built for the assessment task. It's a Laravel-based web application that lets users generate video scripts and scene breakdowns using AI. The app is already deployed on Railway — you can find the link in the description."

---

## Scene 2: Tech Stack Overview (~30 seconds)
**[Show: VS Code with folder structure, or a quick slide]**

> "Here's the stack I used:
> - **Laravel 13** with PHP 8.4 as the backend framework
> - **Blade templates** for views, with **Tailwind CSS** and **Alpine.js** loaded via CDN — so there's no separate frontend build step
> - **SQLite** for local development, **MySQL** for production
> - For the AI provider, I used **OpenRouter API** with Google's **Gemini 2.0 Flash** model
> - And it's deployed on **Railway**"

---

## Scene 3: Demo — Authentication (~30 seconds)
**[Show: Register page → create new account → auto-login → dashboard]**

> "Let's start with authentication. Users can register with name, email, and password. After registering, they're automatically logged in and redirected to the dashboard."

**[Show: Dashboard with stats]**

> "The dashboard displays statistics — total generations, completed, and failed — plus the four most recent generations."

---

## Scene 4: Demo — Generate Video Script (~60 seconds)
**[Show: Click 'Generate Video Script' → form page]**

> "Now let's get to the main feature. The generation form has several inputs: Video Type, Tone, Topic, Keywords, Target Audience, and an optional Duration."

**[Show: Click one of the Quick Templates in the sidebar]**

> "I also implemented **Content Templates** as a bonus feature — there are five presets like Product Launch, Educational Tutorial, Social Media Reel, and so on. Click any template, and the form auto-fills."

**[Show: Submit form, loading spinner appears]**

> "Click Generate, and the button transitions into a loading spinner. The system sends the prompt to OpenRouter API, which usually takes 10 to 30 seconds."

**[Show: Detail page with Script + Scene Breakdown tabs]**

> "Once it's done, the user is redirected to the detail page. There are two tabs: **Full Script**, which contains the complete narration, and **Scene Breakdown**, which shows each scene with its number, duration, narration, and visual direction."

---

## Scene 5: Demo — Export & History (~40 seconds)
**[Show: Click Export .txt button]**

> "Users can export the script as a `.txt` file. The format is clean — header, full script, and a scene-by-scene breakdown."

**[Show: Open the downloaded .txt file]**

> "Here's the result, ready to use."

**[Show: Click History → list of generations]**

> "On the History page, users can browse all their past generations. There's a **search bar** to filter by title or topic, pagination, and each entry can be viewed in detail or deleted."

---

## Scene 6: Quick Code Walkthrough (~60 seconds)
**[Show: VS Code — open VideoController.php]**

> "Let me give a quick look at the architecture. `VideoController` handles all video-related actions. The `store` method is the core:
> - Validate the input
> - Create a record with status `pending`
> - Call `OpenRouterService` to generate via AI
> - Update the record with the script and scenes, set status to `completed`
> - If the API fails, status becomes `failed` and the user gets a friendly error message"

**[Show: Open OpenRouterService.php]**

> "`OpenRouterService` encapsulates the AI call. I use OpenRouter's JSON mode so the response is always structured and directly parseable. The prompt is explicit — it asks for a script plus scene breakdown in JSON format."

**[Show: Open migration / model file]**

> "The database schema is straightforward — a `video_generations` table with a foreign key to users, columns for all the form inputs, plus `script` and `scenes` (as JSON) for the AI output."

---

## Scene 7: Testing & Deployment (~30 seconds)
**[Show: Terminal — run `php artisan test`]**

> "I also wrote a test suite — 16 feature tests covering auth, video generation, search, delete, and export. All passing."

**[Show: Railway dashboard or live URL in browser]**

> "Deployment is on Railway. I push to GitHub, Railway auto-detects Laravel, the MySQL service is attached, environment variables are configured, and it auto-deploys on every push."

---

## Scene 8: Closing (~15 seconds)
**[Show: Live URL homepage]**

> "That's the walkthrough of the AI Video Generator. The live URL and full documentation are in the submission email. Thanks for watching!"

---

## Recording Tips

- **Practice once** before the final recording so the flow is smooth
- **Turn off notifications** on your Mac (Do Not Disturb mode)
- **Resize the browser** to a medium size (not too small so text stays readable)
- **Zoom in** if code looks too small (Cmd + in browser/VS Code)
- **Speak naturally**, like you're explaining to a friend — don't read the script stiffly
- **If you misspeak**, just pause briefly and redo the sentence — you can edit later if needed

---

## Upload to YouTube

1. Open [youtube.com/upload](https://youtube.com/upload)
2. Select your video file
3. Title: **"AI Video Generator — Technical Walkthrough"**
4. Description: paste the Railway URL + documentation reference
5. **Visibility: Unlisted** (sufficient, no need for public)
6. Save → copy the link
7. Paste the link in your submission email

---

## Final Submission Checklist

- [ ] Live URL accessible from another browser (incognito mode test)
- [ ] Demo account created (if needed)
- [ ] OpenRouter API key is valid (test one generation before recording)
- [ ] Documentation (PDF / markdown) is final
- [ ] Video is uploaded, unlisted link is accessible
- [ ] Submission email draft ready to send to `hrbedaieofficial@gmail.com`
