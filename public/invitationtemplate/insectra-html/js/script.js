var THEMEMASCOT = {};
(function($) {

	"use strict";


  /* ---------------------------------------------------------------------- */
  /* --------------------------- Start Demo Switcher  --------------------- */
  /* ---------------------------------------------------------------------- */
  var showSwitcher = false;
  var $body = $('body');
  var $style_switcher = $('#style-switcher');
  if( !$style_switcher.length && showSwitcher ) {
      $.ajax({
          url: "color-switcher/style-switcher.html",
          success: function (data) { $body.append(data); },
          dataType: 'html'
      });
  }
  /* ---------------------------------------------------------------------- */
  /* ----------------------------- En Demo Switcher  ---------------------- */
  /* ---------------------------------------------------------------------- */


  THEMEMASCOT.isRTL = {
    check: function() {
      if( $( "html" ).attr("dir") === "rtl" ) {
        return true;
      } else {
        return false;
      }
    }
  };

  THEMEMASCOT.isLTR = {
    check: function() {
      if( $( "html" ).attr("dir") !== "rtl" ) {
        return true;
      } else {
        return false;
      }
    }
  };

	//Hide Loading Box (Preloader)
	function handlePreloader() {
		if($('.preloader').length){
			$('.preloader').delay(200).fadeOut(500);
		}
	}
	$(document).ready(function () {
    $(".preloader-loaded").addClass("loaded");
    if ($(".preloader-loaded").hasClass("loaded")) {
      $("#preloader").delay(750).queue(function () {
        $(this).remove();
      });
    }
  });

	//Update Header Style and Scroll to Top
	function headerStyle() {
		if($('.main-header').length){
			var windowpos = $(window).scrollTop();
			var siteHeader = $('.header-style-one');
			var scrollLink = $('.scroll-to-top');
			var sticky_header = $('.main-header .sticky-header');
			if (windowpos > 100) {
				sticky_header.addClass("fixed-header animated slideInDown");
				scrollLink.fadeIn(300);
			}else {
				sticky_header.removeClass("fixed-header animated slideInDown");
				scrollLink.fadeOut(300);
			}
			if (windowpos > 1) {
				siteHeader.addClass("fixed-header");
			}else {
				siteHeader.removeClass("fixed-header");
			}
		}
	}
	headerStyle();


	// Backtotop Js
	function back_to_top() {
	var btn = $('#back_to_top');
	var btn_wrapper = $('.back-to-top-wrapper');
	var windowOn = $(window); // Define windowOn properly

	windowOn.on('scroll', function () {
		if (windowOn.scrollTop() > 300) {
			btn_wrapper.addClass('back-to-top-btn-show');
		} else {
			btn_wrapper.removeClass('back-to-top-btn-show');
		}
	});

	btn.on('click', function (e) {
		e.preventDefault();
		$('html, body').animate({
			scrollTop: 0
		}, 300); // Removed quotes from 300, since it's a number
	});
	}

	back_to_top();

	//Submenu Dropdown Toggle
	if($('.main-header li.dropdown ul').length){
		$('.main-header .navigation li.dropdown').append('<div class="dropdown-btn"><i class="fa fa-angle-down"></i></div>');
	}

	//Mobile Nav Hide Show
	if($('.mobile-menu').length){

		var mobileMenuContent = $('.main-header .main-menu .navigation').html();

		$('.mobile-menu .navigation').append(mobileMenuContent);
		$('.sticky-header .navigation').append(mobileMenuContent);
		$('.mobile-menu .close-btn').on('click', function() {
			$('body').removeClass('mobile-menu-visible');
		});

		//Dropdown Button
		$('.mobile-menu li.dropdown .dropdown-btn').on('click', function() {
			$(this).prev('ul').slideToggle(500);
			$(this).toggleClass('active');
		});

		//Menu Toggle Btn
		$('.mobile-nav-toggler').on('click', function() {
			$('body').addClass('mobile-menu-visible');
		});

		//Menu Toggle Btn
		$('.mobile-menu .menu-backdrop, .mobile-menu .close-btn').on('click', function() {
			$('body').removeClass('mobile-menu-visible');
		});

	}

	//Header Search
	if($('.search-btn').length) {
		$('.search-btn').on('click', function() {
			$('.main-header').addClass('moblie-search-active');
		});
		$('.close-search, .search-back-drop').on('click', function() {
			$('.main-header').removeClass('moblie-search-active');
		});
	}


	// Image move with mouse Block
	if ($(".team-block .inner-block").length) {
		$(".team-block .inner-block").each(function () {
			const $item = $(this);
			const $hoverImage = $item.find(".hover-image");

			if ($hoverImage.length === 0) return;

			$item.on("mousemove", function (e) {
				const offset = $item.offset();
				const dx = e.pageX - offset.left;
				const dy = e.pageY - offset.top;

				// ✅ Keep the rotate(30deg) here always
				$hoverImage.css(
					"transform",
					`translate(${dx}px, ${dy}px) rotate(0deg)`
				);
			});
		});
	}

	//service-carousel One
	if ($('.banner-slider-one').length) {
		var swiper = new Swiper(".banner-slider-one", {
			slidesPerView: 1,
			spaceBetween: 0,
			speed: 600,
			loop: true,
			navigation: {
				nextEl: '.swiper-button-next',
				prevEl: '.swiper-button-prev',
			},
		});
	}

// service-carousel One
if ($('.banner-two-slider-down').length) {
    var swiper = new Swiper(".banner-two-slider-down", {
        direction: "vertical",
        slidesPerView: 3,
        spaceBetween: 15,
        loop: true,
        autoplay: {
				    delay: 0,
				    disableOnInteraction: false,
				    reverseDirection: true,
				},
				speed: 2000,
				freeMode: true,
    });
}

// service-carousel One
if ($('.banner-two-slider-up').length) {
    var swiper = new Swiper(".banner-two-slider-up", {
        direction: "vertical",
        slidesPerView: 3,
        spaceBetween: 15,
        loop: true,
        autoplay: {
				    delay: 0,
				    disableOnInteraction: false,
				    reverseDirection: false,
				},
				speed: 2000,
				freeMode: true,
    });
}

	if ($('.service-one-slider').length) {
		var swiper = new Swiper(".service-one-slider", {
			speed:1500,
			loop: true,
			slidesPerView: 1,
			spaceBetween: 0,
			breakpoints: {
				320: {
					slidesPerView: 1,
				},
				576: {
					slidesPerView: 1,
				},
				768: {
					slidesPerView: 2,
				},
				992: {
					slidesPerView: 3,
				},
				1023: {
					slidesPerView: 3,
				},
				1400: {
					slidesPerView: 4,
				},
			},
			// Navigation arrows
			navigation: {
				nextEl: '.slider-next',
				prevEl: '.slider-prev',
			},
		});
	}


	/* ==================================================
		Overlay Animation
	================================================== */

	gsap.registerPlugin(ScrollTrigger);

	let ofsetHeight = document.querySelector(".service-outer-container");
	if (ofsetHeight) {
		ScrollTrigger.matchMedia({
			"(min-width: 220px)": function () {
				let pbmitpanels = gsap.utils.toArray(".service-block");
				const spacer = 0;

				let pbmitheight = pbmitpanels[0].offsetHeight + 0;
				pbmitpanels.forEach((pbmitpanel, i) => {
					TweenMax.set(pbmitpanel, {
						top: i * 0,
						marginBottom: "30px"
					});
					const tween = gsap.to(pbmitpanel, {
						scrollTrigger: {
							trigger: pbmitpanel,
							start: () => `top bottom-=100`,
							end: () => `top top+=40`,
							scrub: true,
							invalidateOnRefresh: true
						},
						ease: "none",
						scale: () => 1 - (pbmitpanels.length - i) * 0
					});
					ScrollTrigger.create({
						trigger: pbmitpanel,
						start: () => "top 40px",
						endTrigger: '.service-outer-container',
						end: `bottom top+=${pbmitheight + (pbmitpanels.length * spacer)}`,
						pin: true,
						pinSpacing: false,
					});
				});
			},
			"(max-width:220px)": function () {
				ScrollTrigger.getAll().forEach(pbmitpanels => pbmitpanels.kill(true));
			}
		});
	}

	if ($('.service-two-slider').length) {
		var swiper = new Swiper(".service-two-slider", {
			speed: 1500,
			loop: true,
			autoplay: {
		    delay: 1500,
		    disableOnInteraction: false,
		  },
			slidesPerView: 1,
			slidesPerGroup: 2,
			spaceBetween: 24,
			breakpoints: {
				320: {
					slidesPerView: 1,
				},
				576: {
					slidesPerView: 1,
				},
				768: {
					slidesPerView: 2,
				},
				992: {
					slidesPerView: 3,
				},
				1023: {
					slidesPerView: 3,
				},
				1400: {
					slidesPerView: 4,
				},
			},
			// pagination
			pagination: {
				el: ".service-two-dots",
				clickable:true,
			},
		});
	}

	if ($('.project-one-slider').length) {
		var swiper = new Swiper(".project-one-slider", {
			speed: 500,
			loop: true,
			autoplay: {
		    delay: 1500,
		    disableOnInteraction: true,
		  },
			slidesPerView: 1,
			spaceBetween: 30,
			breakpoints: {
				320: {
					slidesPerView: 1,
				},
				576: {
					slidesPerView: 1,
				},
				768: {
					slidesPerView: 2,
				},
				992: {
					slidesPerView: 3,
				},
				1023: {
					slidesPerView: 3,
				},
				1400: {
					slidesPerView: 4,
				},
			},
			// Navigation arrows
			navigation: {
				nextEl: '.project-slider-next',
				prevEl: '.project-slider-prev',
			},
		});
	}

	//service-carousel One
	if ($('.clients-swiper-one').length) {
		var swiper = new Swiper(".clients-swiper-one", {
			slidesPerView: 5,
			spaceBetween: 0,
			speed: 500,
			loop: true,
			autoplay: true,
			breakpoints: {
				0: {
					slidesPerView: 1,
				},
				576: {
					slidesPerView: 1,
				},
				768: {
					slidesPerView: 2,
				},
				992: {
					slidesPerView: 3,
				},
				1200: {
					slidesPerView: 5,
				},
				1400: {
					slidesPerView: 6,
				}
			},
		});
	}

	//service-carousel One
	if ($('.testimonial-one-slider').length) {
		var swiper = new Swiper(".testimonial-one-slider", {
			slidesPerView: 1,
			spaceBetween: 30,
			speed: 500,
			loop: true,
			autoplay: {
		    delay: 1500,
		    disableOnInteraction: true,
		  },
			// pagination
			pagination: {
				el: ".testimonial-one-dots",
				clickable:true,
			},
		});
	}

	const boxes = document.querySelectorAll('.service-block-two');

	boxes.forEach(box => {
	  box.addEventListener('mouseenter', () => {
	    boxes.forEach(item => item.classList.remove('active')); // অন্যসব থেকে active remove
	    box.classList.add('active'); // hover করা box active হবে
	  });
	});

	if ($('.testimonial-two-slider').length) {
		var swiper = new Swiper(".testimonial-two-slider", {
			speed: 500,
			loop: true,
			autoplay: {
		    delay: 1500,
		    disableOnInteraction: true,
		  },
			slidesPerView: 1,
			spaceBetween: 24,
			// pagination
			pagination: {
				el: ".testimonial-two-dots",
				clickable:true,
			},
		});
	}

	if ($('.testi-slider-content').length) {
		var swiper = new Swiper(".testi-slider-content", {
			speed:1500,
			loop: true,
			slidesPerView: 1,
			spaceBetween: 70,
			autoplay: true,
		});
	}

	// Background image area start here ***
	$("[data-background").each(function() {
	  $(this).css(
	    "background-image",
	    "url( " + $(this).attr("data-background") + "  )"
	  );
	});

	// Background image hover change area start here ***
	$(".service-block").hover(function() {
	  let newBackground = $(this).data("bg");
	  $(".services-section .outer-box")
	    .attr("data-background", newBackground)
	    .css("background-image", "url(" + newBackground + ")");
	});

	if ($('.swiper-slide .service-block .inner-box').length) {
    $(document).ready(function() {
      const $stepBlocks = $('.swiper-slide .service-block .inner-box');
      let $lastActiveBlock = $stepBlocks.eq(2); // Start with 2nd div active

      // Initialize - set middle block active
      $stepBlocks.removeClass('active');
      $lastActiveBlock.addClass('active');

      // Hover handling using .on()
      $stepBlocks.on('mouseenter', function() {
        $stepBlocks.removeClass('active');
        $(this).addClass('active');
        $lastActiveBlock = $(this);
      });

      $stepBlocks.on('mouseleave', function() {
        $stepBlocks.removeClass('active');
        $lastActiveBlock.addClass('active');
      });
		});
	}

	if ($('.team-block1').length) {
    var $team_block = $('.team-block1 .inner-block');
    $($team_block).on('mouseenter', function (e) {
      $(this).find('.content .social-icon').stop().slideDown(300);
      return false;
    });
    $($team_block).on('mouseleave', function (e) {
      $(this).find('.content .social-icon').stop().slideUp(300);
      return false;
    });
	}

	// 21. Image Reveal Animation  used
  let imgs_reveal = document.querySelectorAll(".img-reveal");

  imgs_reveal.forEach((container) => {
    let image = container.querySelector("img");
    let tl = gsap.timeline({
      scrollTrigger: {
        trigger: container,
        toggleActions: "restart none none reset"
      }
    });

    tl.set(container, { autoAlpha: 1 });
    tl.from(container, 1.5, {
      xPercent: -100,
      ease: Power2.out
    });
    tl.from(image, 1.5, {
      xPercent: 100,
      scale: 1.3,
      delay: -1.5,
      ease: Power2.out
    });
  });



	// Section Title Animation
    if ($('.char-animation').length > 0) {
  let char_come = gsap.utils.toArray(".char-animation");

  char_come.forEach(splitTextLine => {
    const itemSplitted = new SplitText(splitTextLine, { type: "chars, words" });

    const tl = gsap.timeline({
      scrollTrigger: {
        trigger: splitTextLine,
        start: 'top 90%',
        end: 'bottom 10%',
        /* toggleActions parameters:
           onEnter, onLeave, onEnterBack, onLeaveBack
        */
        toggleActions: 'play reverse play reverse',
        markers: false,
      }
    });

    gsap.set(splitTextLine, { perspective: 400 });

    tl.from(itemSplitted.chars, {
      duration: 1.5,
      delay: 0.2,
      x: 100,
      autoAlpha: 0,
      stagger: 0.05,
      ease: "back.out(1.7)",
    });
  });
}



	 /* ================================
      Brand Slider Js Start
    ================================ */

   if ($('.brand-slider').length > 0) {
    const brandSlider = new Swiper(".brand-slider", {
        spaceBetween: 0,
        speed: 1200,
        loop: true,
        autoplay: {
            delay: 1000,
            disableOnInteraction: false,
        },
        navigation: {
            nextEl: ".array-next",
            prevEl: ".array-prev",
        },
        breakpoints: {
            1399: {
                slidesPerView: 6,
            },
            1199: {
                slidesPerView: 5,
            },
            991: {
                slidesPerView: 4,
            },
            767: {
                slidesPerView: 3,
            },
            575: {
                slidesPerView: 2,
            },
						475: {
                slidesPerView: 2,
            },
            0: {
                slidesPerView: 1,
            },
        },
    });
   }

	/* ================================
	team-slider Js Start
	================================ */

	if($('.team-slider').length > 0) {
		const projectSlider = new Swiper(".team-slider", {
			spaceBetween: 20,
			speed: 1500,
			loop: true,
			// autoplay: {
			// 	delay: 1000,
			// 	disableOnInteraction: false,
			// },
			breakpoints: {
				1400: {
					slidesPerView: 4,
				},
				992: {
					slidesPerView: 3,
				},
				768: {
					slidesPerView: 2,
				},
				400: {
					slidesPerView: 1,
				},
			},
		});
	}

	/* ================================
	Testimonial Slider Js Start
	================================ */

	if($('.testimonial-slider').length > 0) {
		const testimonialSlider = new Swiper(".testimonial-slider", {
			spaceBetween: 30,
			speed: 1500,
			loop: true,
			autoplay: {
				delay: 1000,
				disableOnInteraction: false,
			},
			 navigation: {
				nextEl: ".array-prev",
				prevEl: ".array-next",
			},
		});
	}

	//service-carousel One
	if ($('.testi-three-slider').length) {
		var swiper = new Swiper(".testi-three-slider", {
			slidesPerView: 1,
			spaceBetween: 0,
			speed: 600,
			loop: true,
			navigation: {
				nextEl: '.testi-slider-next',
				prevEl: '.testi-slider-prev',
			},
		});
	}

	/* ================================
	service-slider Js Start
	================================ */

	if($('.service-slider').length > 0) {
		const projectSlider = new Swiper(".service-slider", {
			spaceBetween: 30,
			speed: 1500,
			loop: true,
			autoplay: {
				delay: 1000,
				disableOnInteraction: false,
			},
			breakpoints: {
				1400: {
					slidesPerView: 4,
				},
				992: {
					slidesPerView: 3,
				},
				768: {
					slidesPerView: 2,
				},
				400: {
					slidesPerView: 1,
				},
			},
		});
	}

  function show_secondary_price(pricing_tables){
    pricing_tables.addClass('show-secondary-price');
    var pricing_btn = pricing_tables.find('.btn');
    var secondary_btn_url = pricing_btn.data("secondary-link");
    pricing_btn.attr("href", secondary_btn_url);
  }
  function hide_secondary_price(pricing_tables){
    pricing_tables.removeClass('show-secondary-price');
    var pricing_btn = pricing_tables.find('.btn');
    var normal_btn_url = pricing_btn.data("normal-link");
    pricing_btn.attr("href", normal_btn_url);
  }

  //smart btn
  var TM_Pricing_Switcher_Smart = function ($scope) {
    var pricing_smart_switcher = $('.tm-pricing-smart-switcher, .tm-pricing-plan-switcher');
    if( pricing_smart_switcher.length > 0 ) {
      pricing_smart_switcher.find("[data-pricing-trigger]").on("click", function (e) {
        var $self = $(e.target);
        $self.toggleClass("secondary-active");
        var pricing_tables = $self.parents("section").find(".tm-pricing-table");

        if( $self.hasClass( 'secondary-active' ) ) {
          show_secondary_price(pricing_tables);
        } else {
          hide_secondary_price(pricing_tables);
        }
      });
    }
  };

  //round, flat btn
  var TM_Pricing_Switcher_Btn = function ($scope) {
    var pricing_btn_switcher = $('.tm-pricing-smart-switcher-button');
    if( pricing_btn_switcher.length > 0 ) {
      pricing_btn_switcher.find("[data-pricing-trigger]").on("click", function (e) {
        var target_id = $(this).data('show');
        var $self = $(e.target);
        pricing_btn_switcher.find("[data-pricing-trigger]").removeClass("active");
        $(this).addClass("active");
        var pricing_tables = $self.parents("section").find(".tm-pricing-table");

        if( target_id == "year" ) {
          show_secondary_price(pricing_tables);
        } else {
          hide_secondary_price(pricing_tables);
        }
      });
    }
  };

	if ($('.product-details .bxslider').length) {
		$('.product-details .bxslider').bxSlider({
        nextSelector: '.product-details #slider-next',
        prevSelector: '.product-details #slider-prev',
        nextText: '<i class="fa fa-angle-right"></i>',
        prevText: '<i class="fa fa-angle-left"></i>',
        mode: 'fade',
        auto: 'true',
        speed: '700',
        pagerCustom: '.product-details .slider-pager .thumb-box'
    });
	};

	if ($('.room-section-h1-two .bxslider').length) {
		$('.room-section-h1-two .bxslider').bxSlider({
        nextSelector: '.room-section-h1-two #slider-next',
        prevSelector: '.room-section-h1-two #slider-prev',
        nextText: '<i class="fa fa-angle-right"></i>',
        prevText: '<i class="fa fa-angle-left"></i>',
        mode: 'fade',
        auto: 'true',
        speed: '1000',
        pagerCustom: '.room-section-h1-two .slider-pager .thumb-box'
    });
	};

	//Distance Range Slider
	if ($('.distance-range-slider').length) {
		$(".distance-range-slider").slider({
			range: true,
			min: 0,
			max: 2000,
			values: [0, 1500],
			slide: function (event, ui){
				$("input.range-amount").val(ui.values[0] + " - " + ui.values[1]);
			}
		});
		$("input.range-amount").val($(".distance-range-slider").slider("values", 0) + " - " + $(".distance-range-slider").slider("values", 1));
	}

  $(".quantity-box .add").on("click", function () {
    if ($(this).prev().val() < 999) {
      $(this)
        .prev()
        .val(+$(this).prev().val() + 1);
    }
  });
  $(".quantity-box .sub").on("click", function () {
    if ($(this).next().val() > 1) {
      if ($(this).next().val() > 1)
        $(this)
        .next()
        .val(+$(this).next().val() - 1);
    }
  });

	//Price Range Slider
	if($('.price-range-slider').length){
		$( ".price-range-slider" ).slider({
			range: true,
			min: 10,
			max: 99,
			values: [ 10, 60 ],
			slide: function( event, ui ) {
			$( "input.property-amount" ).val( ui.values[ 0 ] + " - " + ui.values[ 1 ] );
			}
		});

		$( "input.property-amount" ).val( $( ".price-range-slider" ).slider( "values", 0 ) + " - $" + $( ".price-range-slider" ).slider( "values", 1 ) );
	}

	//Accordion Box
	if ($('.accordion-box').length) {
		$(".accordion-box").on('click', '.acc-btn', function () {

			var outerBox = $(this).parents('.accordion-box');
			var target = $(this).parents('.accordion');

			if ($(this).hasClass('active') !== true) {
				$(outerBox).find('.accordion .acc-btn').removeClass('active ');
			}

			if ($(this).next('.acc-content').is(':visible')) {
				return false;
			} else {
				$(this).addClass('active');
				$(outerBox).children('.accordion').removeClass('active-block');
				$(outerBox).find('.accordion').children('.acc-content').slideUp(300);
				target.addClass('active-block');
				$(this).next('.acc-content').slideDown(300);
			}
		});
	}

	//Jquery Knob animation  // Pie Chart Animation
	if ($('.dial').length) {
		$('.dial').appear(function () {
			var elm = $(this);
			var color = elm.attr('data-fgColor');
			var perc = elm.attr('value');

			elm.knob({
				'value': 0,
				'min': 0,
				'max': 100,
				'skin': 'tron',
				'readOnly': true,
				'thickness': 0.15,
				'dynamicDraw': true,
				'displayInput': false
			});

			$({ value: 0 }).animate({ value: perc }, {
				duration: 2000,
				easing: 'swing',
				progress: function () {
					elm.val(Math.ceil(this.value)).trigger('change');
				}
			});

			//circular progress bar color
			$(this).append(function () {
				// elm.parent().parent().find('.circular-bar-content').css('color',color);
				//elm.parent().parent().find('.circular-bar-content .txt').text(perc);
			});

		}, { accY: 20 });
	}


	//Fact Counter + Text Count
	if($('.count-box').length){
		$('.count-box').appear(function(){

			var $t = $(this),
				n = $t.find(".count-text").attr("data-stop"),
				r = parseInt($t.find(".count-text").attr("data-speed"), 10);

			if (!$t.hasClass("counted")) {
				$t.addClass("counted");
				$({
					countNum: $t.find(".count-text").text()
				}).animate({
					countNum: n
				}, {
					duration: r,
					easing: "linear",
					step: function() {
						$t.find(".count-text").text(Math.floor(this.countNum));
					},
					complete: function() {
						$t.find(".count-text").text(this.countNum);
					}
				});
			}

		},{accY: 0});
	}



	//Dropdown Button
	$('.pricing-tabs .tab-buttons .yearly').on('click', function() {
		$('.round').addClass('boll-right');
	});

	//Dropdown Button
	$('.pricing-tabs .tab-buttons .monthly').on('click', function() {
		$('.round').removeClass('boll-right');
	});

	//Tabs Box
	if ($('.tabs-box').length) {
		$('.tabs-box .tab-buttons .tab-btn').on('click', function (e) {
			e.preventDefault();
			var target = $($(this).attr('data-tab'));

			if ($(target).is(':visible')) {
				return false;
			} else {
				target.parents('.tabs-box').find('.tab-buttons').find('.tab-btn').removeClass('active-btn');
				$(this).addClass('active-btn');
				target.parents('.tabs-box').find('.tabs-content').find('.tab').fadeOut(0);
				target.parents('.tabs-box').find('.tabs-content').find('.tab').removeClass('active-tab animated fadeIn');
				$(target).fadeIn(300);
				$(target).addClass('active-tab animated fadeIn');
			}
		});
	}

	//Progress Bar
	if ($('.progress-line').length) {
		$('.progress-line').appear(function () {
			var el = $(this);
			var percent = el.data('width');
			$(el).css('width', percent + '%');
		}, { accY: 0 });
	}

	//LightBox / Fancybox
	if($('.lightbox-image').length) {
		$('.lightbox-image').fancybox({
			openEffect  : 'fade',
			closeEffect : 'fade',
			helpers : {
				media : {}
			}
		});
	}

	// Scroll to a Specific Div
	if($('.scroll-to-target').length){
		$(".scroll-to-target").on('click', function() {
			var target = $(this).attr('data-target');
		   // animate
		   $('html, body').animate({
			   scrollTop: $(target).offset().top
			});
		});
	}

	// Home 1 Destination js
	var swiper = new Swiper(".destination-active", {
		speed:1500,
		loop: true,
		slidesPerView: 1,
		autoplay: {
			delay: 3000,            // time between slides (in ms)
			disableOnInteraction: false, // keep autoplay after user interactions
			pauseOnMouseEnter: false,    // optional: autoplay won't pause on hover
		},
		// Navigation arrows
		navigation: {
			nextEl: '.slider-next',
			prevEl: '.slider-prev',
		},
		// pagination
		pagination: {
			el: ".destination-dots",
			clickable:true,
		},
	});

	// Home 1 Destination js
	var swiper = new Swiper(".testimonial-active-home-two", {
		speed:1500,
		loop: true,
		slidesPerView: 1,
		spaceBetween: 50,
		autoplay: {
			delay: 3000,            // time between slides (in ms)
			disableOnInteraction: false, // keep autoplay after user interactions
			pauseOnMouseEnter: false,    // optional: autoplay won't pause on hover
		},
		// Navigation arrows
		navigation: {
			nextEl: '.slider-next',
			prevEl: '.slider-prev',
		},
		// pagination
		pagination: {
			el: ".blog-slider-pagination",
			clickable:true,
		},
	});


	// Aos Animation
	AOS.init();

	// Elements Animation
	if($('.wow').length){
		var wow = new WOW(
		  {
			boxClass:     'wow',      // animated element css class (default is wow)
			animateClass: 'animated', // animation css class (default is animated)
			offset:       0,          // distance to the element when triggering the animation (default is 0)
			mobile:       false,       // trigger animations on mobile devices (default is true)
			live:         true       // act on asynchronously loaded content (default is true)
		  }
		);
		wow.init();
	}

	// count Bar
	if ($(".count-bar").length) {
		$(".count-bar").appear(
			function () {
					var el = $(this);
					var percent = el.data("percent");
					$(el).css("width", percent).addClass("counted");
				}, {
					accY: -50
			}
		);
	}

	//Image Reveal Animation
	if($('.reveal').length){
		gsap.registerPlugin(ScrollTrigger);
		let revealContainers = document.querySelectorAll(".reveal");
		revealContainers.forEach((container) => {
			let image = container.querySelector("img");
			let tl = gsap.timeline({
				scrollTrigger: {
				trigger: container,
				toggleActions: "play none none none"
				}
			});
			tl.set(container, { autoAlpha: 1 });
			tl.from(container, 1.5, {
				xPercent: -100,
				ease: Power2.out
			});
			tl.from(image, 1.5, {
				xPercent: 100,
				scale: 1.3,
				delay: -1.5,
				ease: Power2.out
			});
		});
	}

	document.querySelectorAll(".scroll-text").forEach((section) => {
		let tl = gsap.timeline({
			scrollTrigger: {
			trigger: section,
			start: "top 100%",
			end: "bottom top",
			scrub: 1,
			markers: false,
			},
		});
		tl.from(section.querySelector(".text1"), { xPercent: 20 })
		.from(section.querySelector(".text2"), { xPercent: -20 }, 0);
		tl.from(section.querySelector(".scroll-anim-top"), { yPercent: 10 }, 0)
		.from(section.querySelector(".scroll-anim-bottom"), { yPercent: -10 }, 0);
	});

	//Bg Parallax
	if($('.bg-parallax').length){
		gsap.to(".bg-parallax", {
			backgroundPosition: "70% 75%",
			ease: "ease1",
			scrollTrigger: {
			trigger: ".bg-parallax",
			start: "top bottom",
			end: "bottom top",
			scrub: 1
			}
		});
	}

		// Select2 Dropdown
	$('.custom-select').select2({
		minimumResultsForSearch: 7,
	});

	//Gallery Filters
	 if($('.filter-list').length){
	 	 $('.filter-list').mixItUp({});
	 }

	//Custom Data Attributes
	if($('[data-tm-bg-color]').length){
		$('[data-tm-bg-color]').each(function() {
		  $(this).css("cssText", "background-color: " + $(this).data("tm-bg-color") + " !important;");
		});
	}

	if($('.scroll-to-fixed-parent').length){
	  var scroll_childs = $('.scroll-to-fixed-child');
	  for (var i = 0, length = scroll_childs.length; i < length; i++) {
	    var scroll_child = $(scroll_childs[i]);
	    scroll_child.scrollToFixed({
	      marginTop: $('header').outerHeight(true) + 10,
	      zIndex: 2,
	      spacerClass: 'd-none',
	      removeOffsets: true,
	      limit: function() {
	        var parent = this.parents('.scroll-to-fixed-parent');
	        return parent.offset().top + parent.outerHeight(true) - this.outerHeight(true) - 0;
	      }
	    });
	  }
	 }

  /* ---------------------------------------------------------------------- */
  /* ----------- Activate Menu Item on Reaching Different Sections ---------- */
  /* ---------------------------------------------------------------------- */
  var $onepage_nav = $('.onepage-nav');
  var $sections = $('section');
  var $window = $(window);
  function TM_activateMenuItemOnReach() {
	  if( $onepage_nav.length > 0 ) {
	    var cur_pos = $window.scrollTop() + 2;
	    var nav_height = $onepage_nav.outerHeight();
	    $sections.each(function() {
	      var top = $(this).offset().top - nav_height - 80,
	        bottom = top + $(this).outerHeight();

	      if (cur_pos >= top && cur_pos <= bottom) {
	        $onepage_nav.find('a').parent().removeClass('current').removeClass('active');
	        $sections.removeClass('current').removeClass('active');
	        $onepage_nav.find('a[href="#' + $(this).attr('id') + '"]').parent().addClass('current').addClass('active');
	      }

	      if (cur_pos <= nav_height && cur_pos >= 0) {
	        $onepage_nav.find('a').parent().removeClass('current').removeClass('active');
	        $onepage_nav.find('a[href="#header"]').parent().addClass('current').addClass('active');
	      }
	    });
	  }
	}


/* ==========================================================================
   When document is Scrollig, do
   ========================================================================== */

	$(window).on('scroll', function() {
		headerStyle();
		TM_activateMenuItemOnReach();
	});

/* ==========================================================================
   When document is loading, do
   ========================================================================== */

	$(window).on('load', function() {
		handlePreloader();
		TM_Pricing_Switcher_Smart();
  	TM_Pricing_Switcher_Btn();
	});

})(window.jQuery);
