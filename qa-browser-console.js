/**
 * QA Browser Console Test
 * ========================
 * Copy and paste this entire script into your browser console (F12 → Console)
 * while on the webportal admin page to test all buttons, sounds, and UI elements.
 */
javascript:(function(){
const R={passed:[],failed:[],warnings:[]};
function p(t,d){R.passed.push(t);console.log("%cPASS: "+t+(d?" - "+d:""),"color:green")}
function f(t,d){R.failed.push(t);console.error("%cFAIL: "+t+(d?" - "+d:""),"color:red")}
function w(t,d){R.warnings.push(t);console.warn("%cWARN: "+t+(d?" - "+d:""),"color:orange")}

console.log("%c========================================","color:cyan");
console.log("%cQA BROWSER CONSOLE TEST","color:cyan;font-weight:bold");
console.log("%c========================================","color:cyan");

// ============ DIALPAD TESTS ============
console.log("%c\n--- Dialpad Keys ---","color:yellow");
const dialpadKeys=["1","2","3","4","5","6","7","8","9","0","*","#"];
dialpadKeys.forEach(function(k){
  const sel="[data-value='"+k+"'], .dialpad-key[data-value='"+k+"']";
  if(document.querySelector(sel))p("Dialpad key "+k,"Found");
  else{
    // Try finding by text content
    const keys=document.querySelectorAll(".dialpad-key, [class*='dialpad'] button, [class*='keypad'] button");
    let found=false;
    keys.forEach(function(el){if(el.textContent.trim()===k)found=true});
    if(found)p("Dialpad key "+k,"Found");
    else f("Dialpad key "+k,"Missing");
  }
});

// ============ ACTION BUTTONS ============
console.log("%c\n--- Action Buttons ---","color:yellow");
const actions=["hangup","mute","unmute","hold","resume","transfer","park","record","backspace"];
actions.forEach(function(a){
  const sel="[data-action='"+a+"'], [data-action='"+a+"'] button";
  const btn=document.querySelector(sel);
  if(btn){
    const disabled=btn.disabled||btn.getAttribute("disabled");
    if(disabled)w("Action: "+a,"Disabled");
    else p("Action: "+a,"Enabled");
  }else{
    // Try by text
    const btns=document.querySelectorAll("button");
    let found=false;
    btns.forEach(function(b){if(b.textContent.toLowerCase().includes(a))found=true});
    if(found)p("Action: "+a,"Found by text");
    else f("Action: "+a,"Missing");
  }
});

// ============ DIALPAD DISPLAY ============
console.log("%c\n--- Dialpad Display ---","color:yellow");
if(document.querySelector("#dialpad-display, .dialpad-display, [id*='dialpad'][id*='display']"))p("Dialpad display","Present");
else if(document.querySelector("input[type='tel'], input[placeholder*='number' i]"))p("Dialpad input","Present");
else f("Dialpad display","Missing");

// ============ AUDIO ELEMENTS ============
console.log("%c\n--- Audio Elements ---","color:yellow");
const audios=document.querySelectorAll("audio");
if(audios.length>0){
  p("Audio elements",audios.length+" found");
  audios.forEach(function(a,i){
    console.log("  ["+i+"] id="+a.id+" src="+(a.src||"dynamic")+" muted="+a.muted+" paused="+a.paused);
  });
}else w("Audio elements","Not in HTML (may be dynamically created by WebRTC)");

// ============ VIDEO ELEMENTS ============
console.log("%c\n--- Video Elements ---","color:yellow");
const videos=document.querySelectorAll("video");
if(videos.length>0)p("Video elements",videos.length+" found");
else w("Video elements","None (expected for audio-only calls)");

// ============ WEB PHONE STATUS ============
console.log("%c\n--- Web Phone Status ---","color:yellow");
if(document.querySelector("#web-phone-state, .web-phone-state, [id*='phone'][id*='state']"))p("Web phone status","Present");
else if(document.querySelector(".connectpro-sip-badge, [class*='sip'][class*='badge']"))p("SIP badge","Present");
else f("Web phone status","Missing");

// ============ INCOMING CALL BANNER ============
console.log("%c\n--- Incoming Call Banner ---","color:yellow");
if(document.querySelector("#incoming-call-banner, .incoming-call, [class*='incoming']"))p("Incoming call banner","Present");
else w("Incoming call banner","Not visible (may be hidden when no call)");

// ============ CONTACT WORKSPACE ============
console.log("%c\n--- Contact Workspace ---","color:yellow");
if(document.querySelector("#contact-workspace-panel, .contact-workspace, [id*='contact'][id*='workspace']"))p("Contact workspace","Present");
else w("Contact workspace","Not found");

// ============ CONTACT TABS ============
console.log("%c\n--- Contact Tabs ---","color:yellow");
const tabs=["notes","activity","history","info"];
tabs.forEach(function(t){
  const sel="[data-contact-tab='"+t+"'], [data-tab='"+t+"']";
  if(document.querySelector(sel))p("Tab: "+t,"Present");
  else{
    const allTabs=document.querySelectorAll("[role='tab'], .tab, .nav-link");
    let found=false;
    allTabs.forEach(function(el){if(el.textContent.toLowerCase().includes(t))found=true});
    if(found)p("Tab: "+t,"Found by text");
    else f("Tab: "+t,"Missing");
  }
});

// ============ CONTACT FORM ============
console.log("%c\n--- Contact Form ---","color:yellow");
const formFields=["name","company","phone","email"];
formFields.forEach(function(field){
  const sel="input[name*='"+field+"'], input[id*='"+field+"'], #contact-"+field+"-input";
  if(document.querySelector(sel))p("Field: "+field,"Present");
  else f("Field: "+field,"Missing");
});

// ============ NAVIGATION ============
console.log("%c\n--- Navigation ---","color:yellow");
const navItems=[
  {name:"Dashboard",href:"dashboard"},
  {name:"Dialer",href:"dialer"},
  {name:"Users",href:"users"},
  {name:"Roles",href:"roles"},
  {name:"Settings",href:"settings"},
  {name:"Carriers",href:"carrier"},
  {name:"Recordings",href:"recordings"},
  {name:"Leads",href:"leads"},
  {name:"Campaigns",href:"campaigns"}
];
navItems.forEach(function(n){
  const link=document.querySelector("a[href*='"+n.href+"']");
  if(link)p("Nav: "+n.name,"Present");
  else f("Nav: "+n.name,"Missing");
});

// ============ DARK MODE ============
console.log("%c\n--- Dark Mode ---","color:yellow");
if(document.querySelector("[id*='DarkModeToggle'], [data-darkmode-toggle], .dark-mode-toggle"))p("Dark mode toggle","Present");
else w("Dark mode toggle","Missing");

// ============ FORMS & INPUTS ============
console.log("%c\n--- Forms & Inputs ---","color:yellow");
const forms=document.querySelectorAll("form");
if(forms.length>0)p("Forms",forms.length+" found");
else w("Forms","None on this page");

const inputs=document.querySelectorAll("input");
if(inputs.length>0)p("Input fields",inputs.length+" found");

const textareas=document.querySelectorAll("textarea");
if(textareas.length>0)p("Textareas",textareas.length+" found");

const selects=document.querySelectorAll("select");
if(selects.length>0)p("Select dropdowns",selects.length+" found");

const submitBtns=document.querySelectorAll("button[type='submit'], input[type='submit']");
if(submitBtns.length>0)p("Submit buttons",submitBtns.length+" found");

// ============ TABLES ============
console.log("%c\n--- Tables ---","color:yellow");
const tables=document.querySelectorAll("table");
if(tables.length>0)p("Tables",tables.length+" found");
else w("Tables","None on this page");

// ============ MODALS ============
console.log("%c\n--- Modals ---","color:yellow");
const modals=document.querySelectorAll(".modal, [role='dialog'], [data-modal]");
if(modals.length>0)p("Modals",modals.length+" found");
else w("Modals","None visible");

// ============ WEB RTC ============
console.log("%c\n--- WebRTC ---","color:yellow");
if(window.DialerWebRTC)p("DialerWebRTC library","Loaded");
else w("DialerWebRTC library","Not loaded");

if(window.webrtcClient){
  p("WebRTC client","Initialized");
  if(window.webrtcClient.simpleUser)p("SimpleUser","Connected");
  else w("SimpleUser","Not initialized");
}else w("WebRTC client","Not initialized");

// ============ AUDIO CONTEXT TEST ============
console.log("%c\n--- Audio Context Test ---","color:yellow");
try{
  const AudioContext=window.AudioContext||window.webkitAudioContext;
  if(AudioContext){
    p("AudioContext API","Supported");
    const ctx=new AudioContext();
    p("AudioContext creation","Success (state: "+ctx.state+")");
    
    // Test oscillator (DTMF-like tone)
    const osc=ctx.createOscillator();
    const gain=ctx.createGain();
    osc.connect(gain);
    gain.connect(ctx.destination);
    gain.gain.value=0.1;
    osc.frequency.value=440;
    osc.start();
    osc.stop(ctx.currentTime+0.1);
    p("Audio generation","Success - oscillator test passed");
    ctx.close();
  }else f("AudioContext API","Not supported");
}catch(e){
  f("AudioContext test",e.message);
}

// ============ MICROPHONE PERMISSION ============
console.log("%c\n--- Microphone Permission ---","color:yellow");
if(navigator.permissions){
  navigator.permissions.query({name:"microphone"}).then(function(result){
    if(result.state==="granted")p("Microphone","Granted");
    else if(result.state==="prompt")w("Microphone","Not yet requested");
    else f("Microphone","Denied");
  }).catch(function(){
    w("Microphone","Cannot query permission");
  });
}

// ============ CLICK TEST ============
console.log("%c\n--- Click Tests ---","color:yellow");
const firstDialpadKey=document.querySelector(".dialpad-key, [data-value='1']");
if(firstDialpadKey){
  try{
    firstDialpadKey.click();
    p("Click dialpad key","Success");
  }catch(e){
    f("Click dialpad key",e.message);
  }
}else w("Click dialpad key","No dialpad key found");

// ============ SUMMARY ============
console.log("%c\n========================================","color:cyan");
console.log("%cQA TEST SUMMARY","color:cyan;font-weight:bold");
console.log("%c========================================","color:cyan");
console.log("%cPASSED: "+R.passed.length,"color:green");
console.log("%cFAILED: "+R.failed.length,"color:red");
console.log("%cWARNINGS: "+R.warnings.length,"color:orange");
console.log("%cTOTAL: "+(R.passed.length+R.failed.length+R.warnings.length),"color:cyan");

if(R.failed.length>0){
  console.log("%c\n✗ FAILURES:","color:red");
  R.failed.forEach(function(t,i){console.log("  "+(i+1)+". "+t)});
}
if(R.warnings.length>0){
  console.log("%c\n! WARNINGS:","color:orange");
  R.warnings.forEach(function(t,i){console.log("  "+(i+1)+". "+t)});
}
console.log("%c========================================","color:cyan");

return R;
})();
