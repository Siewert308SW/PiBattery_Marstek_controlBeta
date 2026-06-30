<?php
//													     		 //
// **************************************************************//
//           		 PiBattery Solar Storage                     //
//                    7" Dashboard (index)                       //
// **************************************************************//
//

$initial = null;
?>
<!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>piBattery</title>
<style>
	* { box-sizing: border-box; }
	 html, body { margin:0; padding:0; width:100%; height:100%; overflow:hidden;
		background:#0E1217; color:#E8EDF2;
		font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
	 body { display:flex; align-items:center; justify-content:center; }
	#screen { width:800px; height:480px; padding:12px; display:flex; flex-direction:column; gap:10px; }
	
	.head { display:flex; align-items:center; justify-content:space-between; height:42px; flex:none; }
	.head .left { display:flex; align-items:center; gap:9px; }
	.head .title { font-size:17px; font-weight:500; }
	.head .sub { font-size:13px; color:#8B97A5; }
	.pill { font-size:13px; background:rgba(29,158,117,0.18); color:#5DCAA5; padding:4px 11px; border-radius:20px;
		display:flex; align-items:center; gap:6px; }
	.run { font-size:14px; color:#8B97A5; }
	.warn-pill { position:absolute; top:8px; left:50%; transform:translateX(-50%); z-index:5;
		font-size:13px; background:rgba(240,85,85,0.18); color:#F09595; padding:4px 11px; border-radius:20px;
		display:flex; align-items:center; gap:6px; }
	.sun-pill { position:absolute; top:8px; left:50%; transform:translateX(-50%); z-index:5;
		font-size:13px; color:#5DCAA5; padding:4px 11px; border-radius:20px;
		display:flex; align-items:center; gap:6px; }
	.sun-pill .sep { opacity:0.4; }

	.body { display:flex; gap:10px; flex:1; min-height:0; }
	.col-left { width:212px; display:flex; flex-direction:column; gap:10px; flex:none; }
	.col-right { width:178px; display:flex; flex-direction:column; gap:10px; flex:none; }

	.card { background:#161B22; border:1px solid rgba(255,255,255,0.07); border-radius:10px; }
	.bat { padding:11px 12px; display:flex; flex-direction:column; }
	.bat .top { display:flex; align-items:center; justify-content:space-between; }
	.bat .name { font-size:13px; color:#8B97A5; }
	.bat .badge { font-size:11px; padding:2px 8px; border-radius:10px; }
	.badge.laden { color:#5DCAA5; background:rgba(29,158,117,0.16); }
	.badge.ontladen { color:#85B7EB; background:rgba(55,138,221,0.16); }
	.badge.standby { color:#8B97A5; background:rgba(255,255,255,0.06); }
	.bat .row { display:flex; align-items:center; gap:12px; margin-top:8px; }
	.bat .info { font-size:12px; line-height:1.55; color:#A7B2BF; }
	.chargers-label { font-size:11px; color:#8B97A5; margin:8px 0 5px; }
	.chargers { display:flex; gap:5px; }
	.chg { flex:1; text-align:center; border-radius:7px; padding:6px 0; }
	.chg.on  { background:rgba(29,158,117,0.14); }
	.chg.off { background:rgba(255,255,255,0.05); }
	.chg .w { font-size:12px; font-weight:500; }
	.chg .s { font-size:9px; }
	.chg.on  .w, .chg.on  .s { color:#5DCAA5; }
	.chg.off .w, .chg.off .s { color:#5F6B79; }

	.flow { flex:1; min-width:0; position:relative; overflow:hidden; }
	.stage { position:absolute; left:50%; top:50%; transform:translate(-50%,-50%); width:360px; height:340px; }
	.node { position:absolute; width:70px; text-align:center; }
	.node .circ { width:58px; height:58px; margin:0 auto; border-radius:50%; background:#0E1217;
		display:flex; align-items:center; justify-content:center; }
	.node .val { font-size:13px; font-weight:500; margin-top:3px; }
	.node .lbl { font-size:10px; color:#8B97A5; }
	.node .lbl2 { font-size:10px; color:#8B97A5; margin-bottom:4px;}
	
	.tiles { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
	.tile { background:#161B22; border:1px solid rgba(255,255,255,0.07); border-radius:7px; padding:6px; }
	.tile .k { font-size:11px; color:#8B97A5; }
	.tile .l { font-size:11px; color:#8B97A5; text-align:center;}
	.tile .v { font-size:13px; font-weight:500; text-align:center;}

	.day { padding:11px 12px; flex:1; display:flex; flex-direction:column; }
	.day .h { font-size:12px; color:#8B97A5; margin-bottom:9px; }
	.day .line { display:flex; justify-content:space-between; font-size:12px; margin-bottom:2px; }
	.day .line .k { color:#A7B2BF; }
	.day .line .v { font-weight:500; }
	.day .foot { margin-top:auto; border-top:1px solid rgba(255,255,255,0.08); padding-top:9px; }
	.day .foot .t { display:flex; justify-content:space-between; align-items:baseline; margin-bottom:6px; }
	.day .foot .t .k { font-size:12px; color:#A7B2BF; }
	.day .foot .t .v { font-size:13px; font-weight:500; color:#5DCAA5; }
	.bar { height:6px; background:rgba(255,255,255,0.08); border-radius:4px; overflow:hidden; }
	.bar > div { height:100%; background:#1D9E75; width:0%; }
	.pbar { margin-top:8px; }
	.pbar .t { display:flex; justify-content:space-between; align-items:baseline; margin-bottom:4px; }
	.pbar .t .k { font-size:11px; color:#8B97A5; }
	.pbar .t .v { font-size:12px; font-weight:500; }
</style>
</head>
<body>
<div id="screen">

	<div class="head">
		<div class="left">
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#EF9F27" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
			<span class="title">piBattery</span><span class="sub">Energy display</span>
		</div>
		<div class="center" style="gap:10px;">
			<span class="pill"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#5DCAA5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg><span id="tijd">—</span></span>
			</span>
		</div>
		<div class="left" style="gap:10px;">
			<span class="pill"><svg width="14" height="14" viewBox="0 0 24 24" fill="#5DCAA5"><path d="M13 2 4 14h6l-1 8 9-12h-6z"/></svg><span id="status">—</span></span>
			</span>
		</div>
	</div>

	<div class="body">

		<div class="col-left">
			<!-- piBattery -->
			<div class="card bat" style="flex:1.35;">
				<div class="top"><span class="name">piBattery v1.0</span><span id="pi-state" class="badge standby">—</span></div>
				<div class="row">
					<svg width="54" height="54" viewBox="0 0 58 58">
						<circle cx="29" cy="29" r="24" fill="none" stroke="rgba(255,255,255,0.09)" stroke-width="6"/>
						<circle id="pi-ring" cx="29" cy="29" r="24" fill="none" stroke="#1D9E75" stroke-width="6" stroke-linecap="round" stroke-dasharray="150.8" stroke-dashoffset="150.8" transform="rotate(-90 29 29)"/>
						<text id="pi-soc" x="29" y="33" text-anchor="middle" font-size="14" font-weight="500" fill="#E8EDF2">0%</text>
					</svg>
					<div class="info">
						<div id="pi-avail">— / — kWh</div>
						<div id="pi-time">—</div>
						<div id="pi-power" style="color:#5DCAA5;">—</div>
					</div>
				</div>
				<div class="chargers-label">laden</div>
				<div class="chargers" id="pi-chargers"></div>
				<div class="pbar">
					<div class="t"><span class="k">ontladen</span><span class="v" id="pi-dis-val">—</span></div>
					<div class="bar"><div id="pi-dis-bar" style="background:#85B7EB"></div></div>
				</div>				
			</div>
			<!-- Marstek -->
			<div class="card bat" style="flex:1;">
				<div class="top"><span class="name">Marstek Venus E v3.0</span><span id="ma-state" class="badge standby">—</span></div>
				<div class="row">
					<svg width="54" height="54" viewBox="0 0 58 58">
						<circle cx="29" cy="29" r="24" fill="none" stroke="rgba(255,255,255,0.09)" stroke-width="6"/>
						<circle id="ma-ring" cx="29" cy="29" r="24" fill="none" stroke="#378ADD" stroke-width="6" stroke-linecap="round" stroke-dasharray="150.8" stroke-dashoffset="150.8" transform="rotate(-90 29 29)"/>
						<text id="ma-soc" x="29" y="33" text-anchor="middle" font-size="14" font-weight="500" fill="#E8EDF2">0%</text>
					</svg>
					<div class="info">
						<div id="ma-avail">— / — kWh</div>
						<div id="ma-time">—</div>
						<div id="ma-power" style="color:#85B7EB;">—</div>
					</div>				
				</div>
				<div class="pbar">
					<div class="t"><span class="k">laden</span><span class="v" id="ma-chg-val">—</span></div>
					<div class="bar"><div id="ma-chg-bar" style="background:#5DCAA5"></div></div>
				</div>
				<div class="pbar">
					<div class="t"><span class="k">ontladen</span><span class="v" id="ma-dis-val">—</span></div>
					<div class="bar"><div id="ma-dis-bar" style="background:#85B7EB"></div></div>
				</div>
			</div>
		</div>

		<!-- Energie-flow -->
		<div class="card flow">
			<div class="warn-pill" id="warn-pill" style="display:none;">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#F09595" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
				<span id="warn-text">—</span>
			</div>
			<div class="sun-pill" id="sun-pill" style="display:none;">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#5DCAA5" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
				<span id="sun-rise">—</span>
				<span class="sep">·</span>
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#5DCAA5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
				<span id="sun-set">—</span>
			</div>
			<div class="stage">
				<svg viewBox="0 0 360 340" width="360" height="340" style="position:absolute; inset:0;">
					<path id="pSH" d="M198,73 C 240,118 272,150 286,167" fill="none" stroke="#EF9F27" stroke-width="3" stroke-opacity="0.30"/>
					<path id="pSB" d="M180,79 C 180,150 180,205 180,265" fill="none" stroke="#EF9F27" stroke-width="3" stroke-opacity="0.30"/>
					<path id="pSG" d="M162,73 C 120,118 88,150 74,167" fill="none" stroke="#EF9F27" stroke-width="3" stroke-opacity="0.28"/>
					<path id="pGH" d="M82,206 C 140,244 220,244 278,206" fill="none" stroke="#5F6B79" stroke-width="3" stroke-opacity="0.18"/>
					<path id="pBH" d="M204,280 C 240,250 270,225 286,212" fill="none" stroke="#1D9E75" stroke-width="3" stroke-opacity="0.22"/>
					<path id="pBG" d="M156,280 C 120,250 90,225 74,212" fill="none" stroke="#1D9E75" stroke-width="3" stroke-opacity="0.22"/>

					<g id="gSH">
						<circle r="3.5" fill="#FAC775"><animateMotion dur="2.0s" repeatCount="indefinite" begin="0s"><mpath href="#pSH"/></animateMotion></circle>
						<circle r="3.5" fill="#FAC775"><animateMotion dur="2.0s" repeatCount="indefinite" begin="1.0s"><mpath href="#pSH"/></animateMotion></circle>
					</g>
					<g id="gSB">
						<circle r="4" fill="#FAC775"><animateMotion dur="2.0s" repeatCount="indefinite" begin="0s"><mpath href="#pSB"/></animateMotion></circle>
						<circle r="4" fill="#FAC775"><animateMotion dur="2.0s" repeatCount="indefinite" begin="0.8s"><mpath href="#pSB"/></animateMotion></circle>
					</g>
					<g id="gSG">
						<circle r="3.5" fill="#FAC775"><animateMotion dur="2.6s" repeatCount="indefinite" begin="0s"><mpath href="#pSG"/></animateMotion></circle>
						<circle r="3.5" fill="#FAC775"><animateMotion dur="2.6s" repeatCount="indefinite" begin="1.1s"><mpath href="#pSG"/></animateMotion></circle>
					</g>
					<g id="gGH">
						<circle r="3.5" fill="#8B97A5"><animateMotion dur="2.6s" repeatCount="indefinite" begin="0s"><mpath href="#pGH"/></animateMotion></circle>
						<circle r="3.5" fill="#8B97A5"><animateMotion dur="2.6s" repeatCount="indefinite" begin="1.1s"><mpath href="#pGH"/></animateMotion></circle>
					</g>
					<g id="gBH">
						<circle r="4" fill="#5DCAA5"><animateMotion dur="1.8s" repeatCount="indefinite" begin="0s"><mpath href="#pBH"/></animateMotion></circle>
						<circle r="4" fill="#5DCAA5"><animateMotion dur="1.8s" repeatCount="indefinite" begin="0.9s"><mpath href="#pBH"/></animateMotion></circle>
					</g>
					<g id="gBG">
						<circle r="3.5" fill="#5DCAA5"><animateMotion dur="2.6s" repeatCount="indefinite" begin="0s"><mpath href="#pBG"/></animateMotion></circle>
						</g>
				</svg>

				<div class="node" style="left:145px; top:15px;">
					<div class="lbl2">Zonnepanelen</div>
					<div class="circ" style="border:2px solid #EF9F27;"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#EF9F27" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg></div>
					<div class="val" id="f-solar" style="color:#FAC775;">0,00 kWh</div>
				</div>
				<div class="node" style="left:21px; top:155px;">
					<div class="lbl">Net</div>
					<div class="circ" style="border:2px solid #5F6B79;"><svg width="26" height="26" viewBox="0 0 24 24" fill="#8B97A5"><path d="M13 2 4 14h6l-1 8 9-12h-6z"/></svg></div>
					<div class="val" id="f-p1" style="color:#A7B2BF;">0,00 kWh</div>
					<div class="lbl" id="f-p1label" style="color:#5DCAA5;">export</div>
				</div>
				<div class="node" style="left:269px; top:155px;">
					<div class="lbl">Huis</div>
					<div class="circ" style="border:2px solid #378ADD;"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#85B7EB" stroke-width="2" stroke-linejoin="round"><path d="M3 11l9-7 9 7M5 10v10h14V10"/></svg></div>
					<div class="val" id="f-home" style="color:#85B7EB;">0,00 kWh</div>
				</div>
				<div class="node" style="left:145px; top:259px;">
					<div class="circ" style="border:2px solid #1D9E75;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#5DCAA5" stroke-width="2" stroke-linejoin="round"><rect id="accu-fill" x="5" y="10" width="12" height="5" rx="0.5" fill="#5DCAA5" stroke="none"/><rect x="3" y="8" width="16" height="9" rx="1"/><path d="M21 11v3"/></svg></div>
					<div class="val" id="f-accu" style="color:#5DCAA5;">0,00 kWh</div>
					<div class="lbl">Batterij</div>
				</div>
			</div>
		</div>

		<div class="col-right">
			<div class="tiles">
				<div class="tile"><div class="l">piBattery</div><div class="v" id="t-pibatteryTemp">—</div></div>
				<div class="tile"><div class="l">piBattery RTE</div><div class="v" id="t-prte">—</div></div>
				<div class="tile"><div class="l">Marstek</div><div class="v" id="t-marstekTemp">—</div></div>
				<div class="tile"><div class="l">Marstek RTE</div><div class="v" id="t-mrte">—</div></div>
			</div>
			<div class="card day">
				<div class="h">Vandaag:</div>
				<div class="line"><span class="k">Verbruik Bruto</span><span class="v" id="d-verbruik-bruto">—</span></div>
				<div class="line"><span class="k">Verbruik Netto</span><span class="v" id="d-verbruik-netto" style="color:#85B7EB;">—</span></div>
				<div class="line"><span class="k">PV opwek</span><span class="v" id="d-pv" style="color:#FAC775;">—</span></div>
				<div class="line"><span class="k">Import</span><span class="v" id="d-import" style="color:#F09595;">—</span></div>
				<div class="line"><span class="k">Export</span><span class="v" id="d-export" style="color:#5DCAA5;">—</span></div>
				<div class="foot">
				<div class="h">Voltages:</div>
				<div class="line"><span class="k">Fase 1:</span><span class="v" id="d-fase1">—</span></div>
				<div class="line"><span class="k">Fase 2:</span><span class="v" id="d-fase2" style="color:#85B7EB;">—</span></div>
				<div class="line"><span class="k">Fase 3:</span><span class="v" id="d-fase3" style="color:#5DCAA5;">—</span></div>
				</div>
				<div class="foot">
					<div class="t"><span class="k">Zelfvoorzienend</span><span class="v" id="d-zelf">0%</span></div>
					<div class="bar"><div id="d-zelfbar"></div></div>
				</div>
			</div>
		</div>

	</div>
</div>

<script>
	const initial = <?php echo json_encode($initial); ?>;

	const kw  = w => (w/1000).toFixed(2).replace('.', ',');
	const kwh = v => Number(v).toFixed(2).replace('.', ',');
	const kwhW = v => Number(v).toFixed(0).replace('.', ',');
	const set  = (id, txt) => { const e = document.getElementById(id); if (e) e.textContent = txt; };
	const show = (id, on)  => { const e = document.getElementById(id); if (e) e.style.display = on ? '' : 'none'; };

	function render(d) {
		if (!d) return;

		set('status', d.status);
		set('updated', d.updated);

		// = piBattery
		const pi = d.pibattery;
		set('pi-soc', pi.socPct + '%');
		document.getElementById('pi-ring').setAttribute('stroke-dashoffset', (150.8 * (1 - pi.socPct/100)).toFixed(1));
		set('pi-avail', kwh(pi.available) + ' / ' + kwh(pi.capacity) + ' kWh');
		set('pi-time',  pi.timeLabel ? (pi.timeLabel + ' ' + pi.time) : '—');
		//set('pi-power', pi.powerLabel ? (pi.powerLabel + ' ' + pi.power + ' W') : '—');
		set('pi-power', pi.powerLabel ? (pi.powerLabel) : '—');
		const pis = document.getElementById('pi-state'); pis.textContent = pi.state; pis.className = 'badge ' + pi.state;
		document.getElementById('pi-power').style.color = (pi.state === 'ontladen') ? '#85B7EB' : '#5DCAA5';

		let html = '';
		pi.chargers.forEach(c => {
			html += '<div class="chg ' + (c.on ? 'on' : 'off') + '"><div class="w">' + c.watt + '</div><div class="s">' + (c.on ? 'aan' : 'uit') + '</div></div>';
		});
		document.getElementById('pi-chargers').innerHTML = html;

		// = Marstek
		const ma = d.marstek;
		set('ma-soc', ma.socPct + '%');
		document.getElementById('ma-ring').setAttribute('stroke-dashoffset', (150.8 * (1 - ma.socPct/100)).toFixed(1));
		set('ma-avail', kwh(ma.available) + ' / ' + kwh(ma.capacity) + ' kWh');
		set('ma-time',  ma.timeLabel ? (ma.timeLabel + ' ' + ma.time) : '—');
		//set('ma-power', ma.powerLabel ? (ma.powerLabel + ' ' + ma.power + ' W') : '—');
		set('ma-power', ma.powerLabel ? (ma.powerLabel) : '—');
		const mas = document.getElementById('ma-state'); mas.textContent = ma.state; mas.className = 'badge ' + ma.state;

		// = Flow
		set('f-solar', kwhW(d.flow.solar) + ' W');
		set('f-home',  kwhW(d.flow.home) + ' W');
		set('f-p1',    kwhW(Math.abs(d.flow.p1)) + ' W');
		const pl = document.getElementById('f-p1label');
		if (d.flow.p1 <= -25) { pl.textContent = 'export'; pl.style.color = '#5DCAA5'; }
		else if (d.flow.p1 >= 25) { pl.textContent = 'import'; pl.style.color = '#F09595'; }
		else { pl.textContent = 'balans'; pl.style.color = '#8B97A5'; }
		set('f-accu', kwhW(d.flow.accu.power) + ' W');
		document.getElementById('accu-fill').setAttribute('width', (12 * d.flow.accu.socPct / 100).toFixed(1));

		const TH = 25;
		const producing   = d.flow.solar > TH;
		const charging    = d.flow.accu.dir === 'laden'    && d.flow.accu.power > TH;
		const discharging = d.flow.accu.dir === 'ontladen' && d.flow.accu.power > TH;
		const exporting   = d.flow.p1 < -TH;
		const importing   = d.flow.p1 >  TH;

		show('gSH', producing);					// zon -> huis
		show('gSB', producing && charging);		// zon -> accu (laden)
		show('gSG', producing && exporting && !discharging);    // zon -> net (teruglevering)
		show('gGH', importing);					// net -> huis (import)
		show('gBH', discharging);				// accu -> huis (ontladen)
		show('gBG', discharging && exporting);	// accu -> net (teruglevering)

		// = Tegels
		set('t-prte', d.tiles.tprte + '%');
		set('t-pibatteryTemp', d.tiles.tpTemp + ' °C');
		set('t-marstekTemp', d.tiles.tmTemp + ' °C');
		set('t-mrte', d.tiles.tmrte + '%');

		// = Dag-totalen
		set('d-pv',       kwh(d.day.today.pv) + ' kWh');
		set('d-verbruik-bruto', kwh(d.day.today.verbruik) + ' kWh');
		set('d-verbruik-netto', kwh(d.day.today.verbruikNetto) + ' kWh');
		set('d-import',   kwh(d.day.today.import) + ' kWh');
		set('d-export',   kwh(d.day.today.export) + ' kWh');
		set('d-zelf', d.day.today.zelf + '%');
		document.getElementById('d-zelfbar').style.width = d.day.today.zelf + '%';

		// = Voltages per fase
		if (d.voltages) {
			set('d-fase1', d.voltages.p1Fase1 + ' V');
			set('d-fase2', d.voltages.p1Fase2 + ' V');
			set('d-fase3', d.voltages.p1Fase3 + ' V');
		}

		// = Storingsmelding / zon-op- en ondergang
		if (d.warnings && d.warnings.systemFailure) {
			set('warn-text', d.warnings.systemFailureIssue || 'Storing');
			document.getElementById('warn-pill').style.display = 'flex';
			document.getElementById('sun-pill').style.display = 'none';
		} else {
			document.getElementById('warn-pill').style.display = 'none';
			if (d.various) {
				set('sun-rise', d.various.sunriseTime);
				set('sun-set', d.various.sunsetTime);
			}
			document.getElementById('sun-pill').style.display = 'flex';
		}
		
// = Ontlaad-/laad-balken
		set('pi-dis-val', kwhW(d.pibattery.dischargeW) + ' W');
		document.getElementById('pi-dis-bar').style.width = Math.min(100, d.pibattery.dischargeW / d.pibattery.maxDischargeW * 100) + '%';

		set('ma-dis-val', kwhW(d.marstek.dischargeW) + ' W');
		document.getElementById('ma-dis-bar').style.width = Math.min(100, d.marstek.dischargeW / d.marstek.maxOutputW * 100) + '%';
		set('ma-chg-val', kwhW(d.marstek.chargeW) + ' W');
		document.getElementById('ma-chg-bar').style.width = Math.min(100, d.marstek.chargeW / d.marstek.maxW * 100) + '%';
	}

	if (initial) render(initial);

async function tick() {
    try {
        const r = await fetch('http://192.168.178.3:8080/display.json', {
            cache: 'no-store'
        });
        render(await r.json());
    } catch (e) {}
}
	function fitScreen() {
		const s = Math.min(window.innerWidth / 800, window.innerHeight / 480);
		document.getElementById('screen').style.transform = 'scale(' + s + ')';
	}
	window.addEventListener('resize', fitScreen);
	fitScreen();

	// = Datum + tijd in de tijd-pill (browsertijd van de Pi)
	const klokDagen   = ['zo','ma','di','wo','do','vr','za'];
	const klokMaanden = ['jan','feb','mrt','apr','mei','jun','jul','aug','sep','okt','nov','dec'];
	function klok() {
		const n = new Date();
		const p = x => String(x).padStart(2, '0');
		set('tijd', klokDagen[n.getDay()] + ' ' + n.getDate() + ' ' + klokMaanden[n.getMonth()] + ' ' + n.getFullYear()
			+ ' · ' + p(n.getHours()) + ':' + p(n.getMinutes()) + ':' + p(n.getSeconds()));
	}
	klok();
	setInterval(klok, 1000);

	setInterval(tick, 5000);

</script>
</body>
</html>