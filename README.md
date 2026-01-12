# 🎓 Moodle Gemini AI Content Suite (mod_gemini)

![Moodle Version](https://img.shields.io/badge/Moodle-4.4%2B%20%7C%205.1-orange?style=for-the-badge&logo=moodle)
![License](https://img.shields.io/badge/License-MIT-blue?style=for-the-badge)
![AI Powered](https://img.shields.io/badge/AI-Gemini%203.0%20%2F%20Local%20LLMs-purple?style=for-the-badge)
![Stability](https://img.shields.io/badge/Stability-v1.1%20Stable-green?style=for-the-badge)

**Transform Moodle into an AI-powered content factory.**

`mod_gemini` is a professional-grade activity module for Moodle that leverages Large Language Models (LLMs) to automatically generate high-quality, multimodal educational resources. Optimized for **Google Gemini 3**, it empowers educators to create engaging, accessible, and pedagogically sound courses in seconds.

---

## 🌟 Key Features

### 1. Advanced Content Generation (Asynchronous)
Unlike other plugins, `mod_gemini` uses a background task system. Teachers can queue multiple generations without waiting at the screen.
*   **📊 Interactive Presentations:** Slide decks with structured text, speaker notes, and **AI-generated visual art** (via Pollinations.ai) for every slide.
*   **🗂️ Gamified Flashcards:** Interactive 3D-flippable cards. Includes **Keyboard Accessibility** (Tab/Enter) and **Gradebook Integration** (students get 100% grade upon completion).
*   **🎙️ AI Audio Lessons:** Generates an educational script and synthesizes it into a **persistent MP3 file** stored in Moodle's file system using OpenAI-compatible TTS.
*   **📝 Smart Summaries with AI-Glossary:** Generates HTML summaries with automated **Tooltip definitions** (`<abbr>`) for complex terms, identified and defined by the AI.
*   **❓ Quiz Generator (Moodle XML):** Generates banks of multiple-choice questions ready to be imported into the Moodle Question Bank.

### 2. Expert Teacher Toolkit
*   **🤖 Rubric Generator:** Instant creation of detailed assessment rubrics (HTML tables) based on the current activity topic.
*   **✏️ Live Editor:** Fine-tune AI outputs. Edit the raw text, HTML, or JSON before publishing.
*   **🔄 Background Queue:** Track the status of your generations (Pending, Processing, Done, Error) with real-time feedback.
*   **💡 Prompt Inspiration:** Built-in "Prompt Chips" to help teachers craft effective AI instructions (e.g., "Explain like I'm 10", "Critical Analysis").

---

## 🏗️ Architecture & Compliance

### 🔒 Privacy & GDPR
*   **Privacy API Support:** Fully compliant with Moodle's Privacy API. Supports data export and "Right to be Forgotten" (deletion of user-related prompts).
*   **Queue Cleanup:** Includes a scheduled task that automatically cleans up old generation logs every 30 days.

### 💾 Reliability
*   **Backup & Restore:** Full support for Moodle's Backup/Restore API. Content generated in one course can be safely backed up and restored in another without data loss.
*   **Local LLM Ready:** Compatible with **LM Studio**, **Ollama**, or any OpenAI-compatible API for privacy-conscious institutions.

### ♿ Accessibility
*   **WCAG Friendly:** Interactive elements like Flashcards support keyboard navigation and ARIA labels.

---

## 🛠️ Installation & Setup

### Requirements
*   Moodle 4.4+ (Full Moodle 5.1 support).
*   PHP 8.1+.
*   An API Key for **Google Gemini 3** (or a local LLM).

### Quick Start
1.  Clone the repo: `git clone https://github.com/hyukudan/moodle-mod-gemini.git gemini` into your `mod` folder.
2.  Install via **Site Administration > Notifications**.
3.  Configure API credentials at **Plugins > Activity modules > Gemini AI Content**.
    - **Default Model:** `gemini-3.0-flash`
    - **Base URL:** `https://generativelanguage.googleapis.com/v1beta/openai/`

---

## 🌍 Internationalization
Available in:
- 🇺🇸 **English** (Native)
- 🇪🇸 **Spanish** (Native)

---

## 📄 License
This project is licensed under the **MIT License**.

*Built with ❤️ for the future of AI-enhanced education.*