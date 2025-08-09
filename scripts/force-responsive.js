(function(){
  function apply(){
    const w = window.innerWidth;
    const h = window.innerHeight;
    const isPortrait = h > w; // simple orientation check

    /* Revised logic:
       Previous version collapsed layout whenever height < 1000, which caused
       most laptops (e.g. 1366x768) to always get the stacked / centered QR.
       We now collapse ONLY when effective width is below 900px, or when in
       portrait orientation AND width <= 1100px (tablet / rotated devices).
    */
    if (w < 900) {
      document.body.classList.add('responsive-collapse');
    } else {
      document.body.classList.remove('responsive-collapse');
    }

    if (isPortrait && w <= 1100){
      document.body.classList.add('responsive-portrait');
    } else {
      document.body.classList.remove('responsive-portrait');
    }
  }
  ['resize','orientationchange'].forEach(evt=>window.addEventListener(evt, apply));
  document.addEventListener('DOMContentLoaded', apply);
})();
