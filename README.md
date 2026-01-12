# 🎓 Moodle Gemini AI Content Suite (mod_gemini)

![Moodle Version](https://img.shields.io/badge/Moodle-4.4%2B%20%7C%205.1-orange?style=for-the-badge&logo=moodle)
![License](https://img.shields.io/badge/License-MIT-blue?style=for-the-badge)
![AI Powered](https://img.shields.io/badge/AI-Gemini%20%2F%20Local%20LLMs-purple?style=for-the-badge)

**Transform Moodle into an AI-powered content factory.**

`mod_gemini` is a next-generation activity module for Moodle that leverages Large Language Models (LLMs) like **Google Gemini 3** to automatically generate high-quality, multimodal educational resources. Designed for both cloud (Google Gemini 3.0) and privacy-first local inference (LM Studio), it empowers educators to create engaging courses in seconds.

---

## 🌟 Key Features

### 1. Instant Content Generation
Stop staring at a blank page. Enter a topic, and let the **Gemini 3** AI build the resource.
*   **📊 Interactive Presentations:** Generates slide decks complete with structured bullet points, speaker notes, and **AI-generated visual art** (via Pollinations.ai) for every slide.
*   **🗂️ Gamified Flashcards:** Creates 3D-flippable study cards. Fully integrated with Moodle's **Gradebook**—students receive a grade upon completing the deck.
*   **🎙️ AI Audio Lessons:** Automatically writes an educational script and synthesizes it into a **high-quality MP3 file** (using OpenAI-compatible TTS) stored securely in Moodle's file system.
*   **📝 Smart Summaries:** Generates clean, HTML-formatted summaries with automated **AI-Glossary tooltips** powered by Gemini 3.
*   **❓ Quiz Generator (Moodle XML):** The "killer feature" for assessment. Generates a bank of multiple-choice questions in standard **Moodle XML** format, ready to be imported directly into your Question Bank.

### 2. Teacher Productivity Toolkit
We don't just generate content; we give you tools to manage it.
*   **🤖 Rubric Generator:** Need to grade an assignment? One click generates a detailed, tiered HTML rubric table based on your content's topic.
*   **✏️ Live Editor:** Full control. Edit the raw text or JSON of any generated content before students see it.
*   **🔄 Regenerate & Reset:** Not happy with the output? Wipe the slate clean and try a new prompt instantly.

### 3. Enterprise & Local Ready
*   **Google Gemini 3 Native:** Built to use Google's latest **Gemini 3.0** models for state-of-the-art speed and pedagogical intelligence.
*   **LM Studio / Local LLM Support:** Fully configurable API endpoints allow you to point this plugin at your own local inference server (e.g., `localhost:1234`). Keep your data on-premise.

---

## 🛠️ Installation

### Prerequisites
*   Moodle 4.4 or higher (Compatible with Moodle 5.1).
*   PHP 8.1+.
*   An API Key (Google Gemini 3) OR a running instance of LM Studio/Ollama.

### Step-by-Step
1.  **Download:** Clone this repository into your Moodle's `mod` directory:
    ```bash
    cd /path/to/moodle/mod
    git clone https://github.com/hyukudan/moodle-mod-gemini.git gemini
    ```
2.  **Install:** Log in to your Moodle site as an Administrator. Moodle will detect the new plugin and prompt you to install it.
3.  **Configure:** Go to **Site Administration > Plugins > Activity Modules > Gemini AI Content**.

---

## ⚙️ Configuration Guide

### Connection Settings
*   **API Key:** Enter your Google Gemini API key here. (If using LM Studio, enter any random string like `lm-studio`).
*   **Base URL:**
    *   **Google Gemini:** `https://generativelanguage.googleapis.com/v1beta/openai/`
    *   **Local (LM Studio):** `http://localhost:1234/v1/`
*   **Model Name:** The model ID to target (e.g., `gemini-3.0-flash`, `llama-3-8b-instruct`).

### Text-to-Speech (Audio)
*   **TTS URL:** Endpoint for audio generation. Defaults to the Base URL + `audio/speech`.
*   **Voice:** Select the voice persona (e.g., `alloy`, `nova`, `shimmer`).

---

## 📖 How to Use

### For Teachers
1.  **Add Activity:** Turn editing on, click "Add an activity or resource", and select **Gemini Content**.
2.  **Wizard:** Enter the activity. You will see the **Creator Wizard**.
3.  **Select & Generate:** Choose a type (e.g., Flashcards), enter a topic (e.g., "The French Revolution"), and click **Generate**.
4.  **Review:** The content appears instantly. Use the **Teacher Tools** panel at the top to Edit, Regenerate, or create a Rubric.

### Importing Quizzes
1.  Generate a **Quiz** activity.
2.  Click the **⬇️ Download XML Questions** button.
3.  Go to **Course Administration > Question Bank > Import**.
4.  Select **Moodle XML format**, upload your file, and import.

---

## 🤝 Contributing
Contributions are welcome! Please submit Pull Requests to the `main` branch.

## 📄 License
This project is licensed under the **MIT License**.

---
*Built with ❤️ for the Moodle Community.*
