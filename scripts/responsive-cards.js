/* Attendance table -> cards + QR details toggle */
(function(){
  const TABLE_ID = 'attendanceTable';
  const cardsContainer = document.getElementById('attendanceCards');
  const toggleBtn = document.getElementById('qrDetailsToggle');
  const qrContainer = document.querySelector('.qr-container');
  if(!cardsContainer) return;

  function buildCards(){
    const table = document.getElementById(TABLE_ID);
    if(!table) return;
    const heads = Array.from(table.querySelectorAll('thead th')).map(th=>th.textContent.trim());
    const rows = table.querySelectorAll('tbody tr');
    cardsContainer.innerHTML='';
    rows.forEach(tr => {
      const tds = tr.children;
      if(!tds.length) return;
      const card = document.createElement('div');
      card.className = 'attendance-card';
      const title = (tds[1] && tds[1].textContent.trim()) || 'Student';
      card.innerHTML = '<h6>'+title+'</h6>';
      Array.from(tds).forEach((td,i)=>{
        const label = heads[i]||'Field';
        if(i===0||i===tds.length-1) return; // skip index & action for compactness
        const val = td.textContent.trim();
        const field = document.createElement('div');
        field.className='field';
        field.innerHTML='<span>'+label+':</span><span>'+val+'</span>';
        card.appendChild(field);
      });
      // actions
      const actionCell = tds[tds.length-1];
      if(actionCell){
        const acts = actionCell.querySelectorAll('button,a');
        if(acts.length){
          const wrap = document.createElement('div');
            wrap.className='actions';
            acts.forEach(a=>wrap.appendChild(a.cloneNode(true)));
            card.appendChild(wrap);
        }
      }
      cardsContainer.appendChild(card);
    });
  }

  function responsiveHandler(){
    if(window.innerWidth <= 500){ buildCards(); }
  }
  window.addEventListener('resize', responsiveHandler);
  document.addEventListener('DOMContentLoaded', responsiveHandler);

  // QR details toggle
  if(toggleBtn && qrContainer){
    toggleBtn.addEventListener('click', () => {
      const hidden = qrContainer.classList.toggle('qr-details-hidden');
      // toggle visibility of hidden elements (those not scanner-con)
      qrContainer.querySelectorAll(':scope > *:not(.scanner-con)').forEach(el=>{
        el.style.display = hidden ? 'none' : '';
      });
      toggleBtn.classList.toggle('active', !hidden);
    });
  }
})();
