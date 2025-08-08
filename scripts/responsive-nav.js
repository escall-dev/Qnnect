/* Basic Sidebar Toggle & Persistence (re-added after revert)
 * Works with .sidebar (.active collapsed) and optional .sidebar-toggle button
 * Also supports .bx-menu icon inside sidebar-nav include
 */
(function(){
	const sidebar = document.querySelector('.sidebar');
	if(!sidebar) return;
	const main = document.querySelector('.main');
	const icon = document.querySelector('.bx-menu');
	let btn = document.querySelector('.sidebar-toggle');
	const LS_KEY = 'sidebar_collapsed';
	const PRESERVE_PARENTS = ['Leaderboards','Data Reports','History']; // menu group titles to not auto-collapse on submenu click

	function ensureButton(){
		if(!btn){
			btn = document.createElement('button');
			btn.className = 'sidebar-toggle';
			btn.setAttribute('aria-label','Toggle menu');
			btn.textContent = '≡';
			document.body.appendChild(btn);
		}
	}
	ensureButton();

	function setState(collapsed, persist=true){
		sidebar.classList.toggle('active', collapsed); // existing CSS uses .active for collapsed width
		if(main) main.classList.toggle('active', collapsed);
		if(persist) localStorage.setItem(LS_KEY, collapsed ? '1':'0');
	}

	function toggle(){ setState(!sidebar.classList.contains('active')); }

	btn.addEventListener('click', toggle);
	if(icon){ icon.addEventListener('click', function(e){ e.preventDefault(); toggle(); }); }

	// restore
	const saved = localStorage.getItem(LS_KEY);
	if(saved === '1') setState(true,false);

	// Prevent collapse on submenu link clicks for specified parents
	const submenuLinks = sidebar.querySelectorAll('.sub-menu a');
	submenuLinks.forEach(a => {
		// find ancestor li containing .link_name text
		const parentLi = a.closest('li');
		if(!parentLi) return;
		// Determine root group label by climbing until immediate child of .nav-links containing .iocn-link or anchor with .link_name
		const group = a.closest('li');
		let groupTitle = null;
		// Try to read preceding sibling with .link_name inside .iocn-link
		const iconLink = group.closest('li')?.querySelector('.iocn-link .link_name');
		if(iconLink) groupTitle = iconLink.textContent.trim();
		if(!groupTitle){
			// fallback: if this link itself has link_name and is first child
			if(a.classList.contains('link_name')) groupTitle = a.textContent.trim();
		}
		if(groupTitle && PRESERVE_PARENTS.includes(groupTitle)){
			a.addEventListener('click', function(){
				// Re-apply open state immediately so it does not animate closed
				// (In case other scripts toggle it on navigation)
				setTimeout(()=>{ setState(false); }, 0);
			});
		}
	});
})();
