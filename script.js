// script.js
console.log("E-learning platform script loaded successfully!");

// Future JavaScript functionality can be added here.
// For example:
// 1. Form validation for search and login/signup.
// 2. Dynamic loading of courses using an API.
// 3. Interactive animations on scroll.

document.addEventListener('DOMContentLoaded', function () {
    // Example: Add a simple interaction
    const courseCards = document.querySelectorAll('.course-card');
    courseCards.forEach(card => {
        card.addEventListener('click', () => {
            const title = card.querySelector('.card-title').innerText;
            console.log(`You clicked on the course: ${title}`);
            // In a real app, this would navigate to the course details page.
        });
    });
});