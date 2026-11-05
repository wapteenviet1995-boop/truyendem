var S=[];
var storiesLoading=true,storiesLoadError="";
var $=function(s){return document.querySelector(s)};
var filter="all",cur=null,idx=0,playing=false,rate=1,fs=1.15,voice=null,token=0;
function esc(s){return s.replace(/[&<>]/g,function(c){return{"&":"&amp;","<":"&lt;",">":"&gt;"}[c]})}
function render(){
  var h="";
  S.filter(function(s){return filter=="all"||(filter=="audio"?s.au:filter=="text"?!s.au:filter=="saved"?savedIds().indexOf(s.id)>-1:false)}).forEach(function(s){
    h+='<li><button class="row" data-id="'+s.id+'"><div class="cover" style="background:'+s.c+'">'+esc(s.t)+'</div><div class="info"><h2 class="t">'+esc(s.t)+(s.au?'<span class="badge">Nghe được</span>':'')+'</h2><p class="meta">'+esc(s.a)+' • '+s.m+' phút</p><p class="desc">'+esc(s.d)+'</p></div></button></li>';
  });
  $("#list").innerHTML=h||'<li class="meta" style="padding:20px 0">'+(filter=="saved"?(me?"Bạn chưa lưu truyện nào. Mở một truyện rồi bấm “♡ Lưu”.":"Đăng nhập để xem các truyện bạn đã lưu."):(storiesLoading?"Đang tải thư viện truyện...":(storiesLoadError||"Chưa có truyện nào được xuất bản.")))+'</li>';
}
document.querySelectorAll(".tab").forEach(function(b){b.onclick=function(){
  filter=b.dataset.f;
  document.querySelectorAll(".tab").forEach(function(x){x.setAttribute("aria-pressed",x===b)});
  render();
}});
$("#list").onclick=function(e){var r=e.target.closest(".row");if(r)openStory(+r.dataset.id)};
async function openStory(id){
  stop();
  var selected=S.filter(function(s){return s.id==id})[0];
  if(!selected)return;
  cur=selected;
  try{
    var response=await fetch("/api/stories/"+encodeURIComponent(selected.slug),{cache:"no-store"});
    var detail=await response.json();
    if(!response.ok||!detail.ok||!detail.story)throw new Error(detail.error||"Không thể tải nội dung truyện.");
    cur.t=detail.story.title;
    cur.a=detail.story.author||"Chưa rõ tác giả";
    cur.au=detail.story.story_type==="audio"||detail.story.story_type==="both";
    cur.p=(detail.chapters||[]).flatMap(function(ch){return ["Chương "+ch.chapter_number+": "+ch.title,ch.content||""]}).filter(Boolean);
    cur.chapterItems=detail.chapters||[];
    if(!cur.p.length)cur.p=["Truyện này chưa có chương được xuất bản."];
  }catch(error){
    cur.p=["Không thể tải truyện lúc này. Vui lòng tải lại trang và thử lại."];
  }
  idx=0;
  var h='<h2>'+esc(cur.t)+'</h2><p class="by">'+esc(cur.a)+'</p>';
  cur.p.forEach(function(p,i){h+='<p data-i="'+i+'">'+esc(p)+'</p>'});
  $("#art").innerHTML=h;
  $("#reader").classList.add("open");$("#reader").scrollTop=0;
  $("#player").classList.toggle("show",cur.au);
  $("#ptitle").textContent=cur.t+" – "+cur.a;
  updateFav();
  mark();
}
$("#back").onclick=function(){stop();$("#reader").classList.remove("open");$("#player").classList.remove("show")};
function setFs(d){fs=Math.min(1.7,Math.max(.95,fs+d));$("#art").style.setProperty("--fs",fs+"rem")}
$("#sm").onclick=function(){setFs(-.1)};$("#lg").onclick=function(){setFs(.1)};
function mark(){
  document.querySelectorAll("#art p[data-i]").forEach(function(p){p.classList.toggle("now",playing&&+p.dataset.i===idx)});
  $("#pbar").style.width=(cur?(idx/cur.p.length*100):0)+"%";
  $("#play").textContent=playing?"⏸":"▶";
  $("#play").setAttribute("aria-label",playing?"Tạm dừng":"Phát");
}
function pickVoice(){
  if(!window.speechSynthesis)return;
  var v=speechSynthesis.getVoices().filter(function(x){return /^vi/i.test(x.lang)});
  voice=v[0]||null;
}
if(window.speechSynthesis){pickVoice();speechSynthesis.onvoiceschanged=pickVoice}
function speak(){
  if(!window.speechSynthesis||!cur)return;
  speechSynthesis.cancel();
  var my=++token;
  var u=new SpeechSynthesisUtterance(cur.p[idx]);
  u.lang="vi-VN";if(voice)u.voice=voice;u.rate=rate;
  u.onend=function(){
    if(my!==token||!playing)return;
    if(idx<cur.p.length-1){idx++;mark();scrollTo();speak()}
    else{playing=false;idx=0;mark()}
  };
  u.onerror=function(){if(my===token){playing=false;mark()}};
  playing=true;mark();scrollTo();
  speechSynthesis.speak(u);
  $("#warn").hidden=!!voice;
}
function scrollTo(){var p=document.querySelector('#art p[data-i="'+idx+'"]');if(p)p.scrollIntoView({block:"center",behavior:"smooth"})}
function stop(){token++;playing=false;if(window.speechSynthesis)speechSynthesis.cancel();mark()}
$("#play").onclick=function(){playing?stop():speak()};
$("#next").onclick=function(){if(!cur)return;idx=Math.min(cur.p.length-1,idx+1);playing?speak():(mark(),scrollTo())};
$("#prev").onclick=function(){if(!cur)return;idx=Math.max(0,idx-1);playing?speak():(mark(),scrollTo())};
$("#spd").onclick=function(){
  var r=[1,1.25,1.5,.75];rate=r[(r.indexOf(rate)+1)%r.length];
  $("#spd").textContent=rate+"×";
  if(playing)speak();
};
/* Tài khoản */
var mem={},mode="login";
function sget(k){try{return localStorage.getItem(k)}catch(e){return mem[k]||null}}
function sset(k,v){try{localStorage.setItem(k,v)}catch(e){mem[k]=v}}
function allUsers(){try{return JSON.parse(sget("td_users")||"{}")}catch(e){return{}}}
function putUsers(u){sset("td_users",JSON.stringify(u))}
var me=sget("td_session")||null;if(me&&!allUsers()[me])me=null;
function hex(b){return Array.prototype.map.call(new Uint8Array(b),function(x){return("0"+x.toString(16)).slice(-2)}).join("")}
function sha(s){return crypto.subtle.digest("SHA-256",new TextEncoder().encode(s)).then(hex)}
function newSalt(){var a=new Uint8Array(8);crypto.getRandomValues(a);return hex(a)}
function savedIds(){var u=me&&allUsers()[me];return u?u.saved:[]}
function renderAcct(){
  var u=me&&allUsers()[me];
  $("#acct").innerHTML=u?'<span class="hello">Xin chào, '+esc(u.name)+'</span><button class="tab" data-a="out">Đăng xuất</button>':'<button class="tab" data-a="login">Đăng nhập</button><button class="tab" data-a="reg" aria-pressed="true">Đăng ký</button>';
}
$("#acct").onclick=function(e){var b=e.target.closest("[data-a]");if(!b)return;if(b.dataset.a=="out")signOut();else openModal(b.dataset.a)};
function setMode(m){
  mode=m;var reg=m=="reg";
  $("#mt").textContent=reg?"Tạo tài khoản":"Đăng nhập";
  $("#ago").textContent=reg?"Đăng ký":"Đăng nhập";
  $("#tl").setAttribute("aria-pressed",!reg);$("#tr").setAttribute("aria-pressed",reg);
  $("#f-name").classList.toggle("hid",!reg);$("#f-pw2").classList.toggle("hid",!reg);
  $("#a-pw").autocomplete=reg?"new-password":"current-password";
  $("#aerr").textContent="";
}
function openModal(m){setMode(m);$("#modal").classList.add("open");setTimeout(function(){$(m=="reg"?"#a-name":"#a-mail").focus()},30)}
function closeModal(){$("#modal").classList.remove("open");$("#af").reset();$("#aerr").textContent=""}
$("#tl").onclick=function(){setMode("login")};$("#tr").onclick=function(){setMode("reg")};
$("#mx").onclick=closeModal;
$("#modal").onclick=function(e){if(e.target===this)closeModal()};
document.addEventListener("keydown",function(e){if(e.key=="Escape")closeModal()});
function signIn(email){me=email;sset("td_session",email);closeModal();renderAcct();render();updateFav()}
function signOut(){me=null;sset("td_session","");renderAcct();render();updateFav()}
$("#af").onsubmit=function(e){
  e.preventDefault();
  var err=$("#aerr"),name=$("#a-name").value.trim(),email=$("#a-mail").value.trim().toLowerCase(),pw=$("#a-pw").value,pw2=$("#a-pw2").value;
  err.textContent="";
  if(!window.crypto||!crypto.subtle){err.textContent="Trình duyệt này không hỗ trợ tạo tài khoản an toàn.";return}
  if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)){err.textContent="Email chưa đúng định dạng.";return}
  if(pw.length<6){err.textContent="Mật khẩu cần ít nhất 6 ký tự.";return}
  var U=allUsers();
  if(mode=="reg"){
    if(!name){err.textContent="Hãy nhập tên hiển thị.";return}
    if(pw!==pw2){err.textContent="Hai mật khẩu chưa khớp.";return}
    if(U[email]){err.textContent="Email này đã có tài khoản. Hãy chuyển sang Đăng nhập.";return}
    var s=newSalt();
    sha(s+pw).then(function(h){U[email]={name:name,salt:s,hash:h,saved:[]};putUsers(U);signIn(email)});
  }else{
    var r=U[email];
    if(!r){err.textContent="Chưa có tài khoản với email này. Hãy đăng ký.";return}
    sha(r.salt+pw).then(function(h){if(h!==r.hash){err.textContent="Mật khẩu chưa đúng.";return}signIn(email)});
  }
};
function updateFav(){
  var on=!!(cur&&savedIds().indexOf(cur.id)>-1);
  $("#fav").textContent=on?"♥ Đã lưu":"♡ Lưu";$("#fav").setAttribute("aria-pressed",on);
}
$("#fav").onclick=function(){
  if(!me){openModal("login");return}
  var U=allUsers(),u=U[me],i=u.saved.indexOf(cur.id);
  if(i>-1)u.saved.splice(i,1);else u.saved.push(cur.id);
  putUsers(U);updateFav();render();
};
renderAcct();
render();

async function loadRemoteStories(){
  storiesLoading=true;
  storiesLoadError="";
  render();
  try{
    var response=await fetch("/api/stories",{cache:"no-store"});
    var data=await response.json();
    if(!response.ok||!data.ok||!Array.isArray(data.stories)){
      throw new Error(data.error||"Không thể kết nối thư viện truyện.");
    }
    S=data.stories.map(function(s){
      return {id:s.id,slug:s.slug,t:s.title,a:s.author||"Chưa rõ tác giả",m:0,au:s.story_type==="audio"||s.story_type==="both",c:"#4f5964",d:s.description||"",p:[]};
    });
  }catch(error){
    S=[];
    storiesLoadError="Không tải được danh sách truyện. Vui lòng thử tải lại trang.";
  }finally{
    storiesLoading=false;
    render();
  }
}
loadRemoteStories();