const navSlide = () => {
	const burger = document.querySelector('.burger');
	const nav = document.querySelector('.nav-links');
	const navLinks = document.querySelectorAll('.nav-links li');
	
	
	burger.addEventListener('click', ()=> {
		// Toggle nav
		nav.classList.toggle('nav-active');
		
		
		// Animate links
		navLinks.forEach((link, index) => {
			if(link.style.animation){
				link.style.animation = `navLinkFade 0.5s ease forwards ${index / 7 + 0.2}s`;
			} else {
				link.style.animation = `navLinkFade 0.5s ease forwards ${index / 7 + 0.2}s`;
			}
		});

		// Burger animation

		burger.classList.toggle('toggle');
	});

	burger.addEventListener('click', ()=> {
		burger.classList.remove('nav-active');
	});
}


const app = () => {
	navSlide();
}

app();