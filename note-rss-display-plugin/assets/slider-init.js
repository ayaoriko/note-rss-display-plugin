// assets/slider-init.js
document.addEventListener('DOMContentLoaded', function () {
  
  const swiper = new Swiper('.wp-block-note-rss-block .swiper', {
    
    // 表示するスライドの数を'auto'に設定
    slidesPerView: 'auto', 
    
    // スライド間の余白（px）
    spaceBetween: 20, 
    
    pagination: {
      el: '.swiper-pagination',
      clickable: true,
    },

    navigation: {
      nextEl: '.swiper-button-next',
      prevEl: '.swiper-button-prev',
    },
    // --- オプションここまで ---
  });

});