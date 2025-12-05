// Header shrink on scroll
const header = document.getElementById("header");
window.addEventListener("scroll", () => {
    if (scrollY > 50) header.classList.add("scrolled");
    else header.classList.remove("scrolled");
});

// Tidak ada profile dropdown
// Tidak ada logout
// Tidak ada scroll to top
