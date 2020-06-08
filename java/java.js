

    $(document).ready(function() {
        $(".menu-icon").on("click", function() {
            $("nav").toggleClass("showing");
            $("nav ul").toggleClass("showing");
        });
       });

       $('.post-wrapper-one').slick({
          slidesToShow: 3,
          slidesToScroll: 1,
          autoplay: true,
          autoplaySpeed: 4000,
          nextArrow: $('.next.one'),
          prevArrow: $('.prev.one'),
          responsive: [
              {
                breakpoint: 1440,
                settings: {
                  slidesToShow: 3,
                  slidesToScroll: 3
                }
              },
              {     
                breakpoint: 1025,
                settings: {
                  slidesToShow: 2,
                  slidesToScroll: 2,
                  infinite: true,
                  dots: true
                }
              },
              {
                breakpoint: 768,
                settings: {
                  slidesToShow: 2,
                  slidesToScroll: 2
                }
              },
              {
                breakpoint: 540,
                settings: {
                  slidesToShow: 1,
                  slidesToScroll: 1
                }
              }
    // You can unslick at a given breakpoint now by adding:
    // settings: "unslick"
    // instead of a settings object
  ]
});


       $('.post-wrapper-two').slick({
          slidesToShow: 3,
          slidesToScroll: 1,
          autoplay: true,
          autoplaySpeed: 4000,
          nextArrow: $('.next.two'),
          prevArrow: $('.prev.two'),
          responsive: [
              {
                breakpoint: 1440,
                settings: {
                  slidesToShow: 3,
                  slidesToScroll: 3
                }
              },
              {     
                breakpoint: 1025,
                settings: {
                  slidesToShow: 2,
                  slidesToScroll: 2,
                  infinite: true,
                  dots: true
                }
              },
              {
                breakpoint: 768,
                settings: {
                  slidesToShow: 2,
                  slidesToScroll: 2
                }
              },
              {
                breakpoint: 540,
                settings: {
                  slidesToShow: 1,
                  slidesToScroll: 1
                }
              }
    // You can unslick at a given breakpoint now by adding:
    // settings: "unslick"
    // instead of a settings object
  ]
});

       

    $(window).on("scroll", function() {
        if($(this).scrollTop() > 200) {
            $('.menu').addClass('sticky');
   		}

        else {
            $('.menu').removeClass('sticky');
             }
      })
    // window.addEventListener("scroll", function(){
    // 	var menu = document.querySelector("");
    // 	menu.classList.toggle("sticky", window.scrollY > 0);
    // })
 $(window).on("scroll", function() {
        if($(this).scrollTop() > 200) {
            $('.booking').addClass('sticky');
      }

        else {
            $('.booking').removeClass('sticky');
             }
      })
 $(window).on("scroll", function() {
        if($(this).scrollTop() > 200) {
            $('.logo').addClass('sticky');
      }

        else {
            $('.logo').removeClass('sticky');
             }
      })
 
 

 // MORE Infomation

 var accordions = document.getElementsByClassName("accordion");

for (var i = 0; i < accordions.length; i++) {
  accordions[i].onclick = function() {
    this.classList.toggle('is-open');

    var content = this.nextElementSibling;
    if (content.style.maxHeight) {
      // accordion is currently open, so close it
      content.style.maxHeight = null;
    } else {
      // accordion is currently closed, so open it
      content.style.maxHeight = content.scrollHeight + "px";
    }
  }
}
