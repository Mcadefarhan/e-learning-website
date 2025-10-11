// script.js
console.log("E-learning platform script loaded successfully!");

// Course card click logging
document.addEventListener('DOMContentLoaded', function () {
    const courseCards = document.querySelectorAll('.course-card');
    courseCards.forEach(card => {
        card.addEventListener('click', () => {
            const title = card.querySelector('.card-title').innerText;
            console.log(`You clicked on the course: ${title}`);
        });
    });

    // Language selector logic
    const langOptions = document.querySelectorAll(".lang-option");
    const langDisplay = document.getElementById("selected-lang");

    const translations = {
        en: {
            heroTitle: "New skills, new future",
            heroSubtitle: "Start learning from the world's best instructors. Find the right course for you.",
            popularCourses: "Our Most Popular Courses",
            categories: "Popular Categories",
            testimonials: "What Our Students Say"
        },
        hi: {
            heroTitle: "नई स्किल्स, नया भविष्य",
            heroSubtitle: "दुनिया के बेहतरीन प्रशिक्षकों से सीखना शुरू करें। अपने लिए सही कोर्स खोजें।",
            popularCourses: "हमारे सबसे लोकप्रिय कोर्स",
            categories: "लोकप्रिय श्रेणियाँ",
            testimonials: "हमारे छात्रों की राय"
        },
        fr: {
            heroTitle: "Nouvelles compétences, nouvel avenir",
            heroSubtitle: "Apprenez des meilleurs instructeurs du monde. Trouvez le bon cours pour vous.",
            popularCourses: "Nos cours les plus populaires",
            categories: "Catégories populaires",
            testimonials: "Ce que disent nos étudiants"
        }
    };

    langOptions.forEach(option => {
        option.addEventListener("click", function(e) {
            e.preventDefault();
            const selectedLang = this.getAttribute("data-lang");
            langDisplay.textContent = this.textContent;
            localStorage.setItem("preferredLang", selectedLang);
            changeLanguage(selectedLang);
        });
    });

    function changeLanguage(lang) {
        const t = translations[lang];
        document.querySelector(".hero-section h1").textContent = t.heroTitle;
        document.querySelector(".hero-section p.lead").textContent = t.heroSubtitle;
        document.querySelector("#courses h2").textContent = t.popularCourses;
        document.querySelector("#categories h2").textContent = t.categories;
        document.querySelector("#testimonials h2").textContent = t.testimonials;
    }

    // Load last selected language from localStorage
    const savedLang = localStorage.getItem("preferredLang") || "en";
    changeLanguage(savedLang);
    langDisplay.textContent = document.querySelector(`.lang-option[data-lang="${savedLang}"]`).textContent;
});
