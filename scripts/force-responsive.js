(function(){
  function apply(){
    const w = window.innerWidth;
    const h = window.innerHeight;
    const isPortrait = h > w; // simple orientation check
    // Collapse criteria: existing (<1000 any dimension) OR portrait with width <=1100
    if (w < 1000 || h < 1000) {
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
