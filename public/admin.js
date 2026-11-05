const $ = s => document.querySelector(s);
const authPanel=$("#auth-panel"), dashboard=$("#dashboard");
async function api(path, options={}) {
  const response=await fetch(path,{credentials:"same-origin",headers:{"content-type":"application/json",...(options.headers||{})},...options});
  const data=await response.json().catch(()=>({ok:false,error:"Máy chủ trả về dữ liệu không hợp lệ."}));
  if(!response.ok || data.ok===false) throw new Error(data.error||"Có lỗi xảy ra.");
  return data;
}
function message(el,text,isError=false){el.textContent=text;el.style.color=isError?"#e59a8a":"var(--mute)"}
function showDashboard(user){authPanel.hidden=true;dashboard.hidden=false;$("#admin-welcome").textContent="Đang đăng nhập: "+user.email;loadStories()}
function showLogin(){authPanel.hidden=false;dashboard.hidden=true}
$("#login-form").addEventListener("submit",async e=>{
 e.preventDefault();const m=$("#auth-message");message(m,"Đang đăng nhập...");
 try{const r=await api("/api/auth/login",{method:"POST",body:JSON.stringify({email:$("#login-email").value,password:$("#login-password").value})});showDashboard(r.user)}
 catch(err){message(m,err.message,true)}
});
$("#setup-form").addEventListener("submit",async e=>{
 e.preventDefault();const m=$("#auth-message");message(m,"Đang tạo tài khoản quản trị...");
 try{const r=await api("/api/admin/setup",{method:"POST",body:JSON.stringify({setup_key:$("#setup-key").value,name:$("#setup-name").value,email:$("#setup-email").value,password:$("#setup-password").value})});showDashboard(r.user)}
 catch(err){message(m,err.message,true)}
});
$("#logout").onclick=async()=>{try{await api("/api/auth/logout",{method:"POST",body:"{}"})}catch{}showLogin()};
function resetForm(){ $("#story-form").reset();$("#story-id").value="";$("#form-title").textContent="Thêm truyện mới";$("#save-story").textContent="Lưu truyện";$("#cancel-edit").hidden=true;message($("#form-message"),"")}
$("#cancel-edit").onclick=resetForm;
$("#story-form").addEventListener("submit",async e=>{
 e.preventDefault();const id=$("#story-id").value;
 const payload={title:$("#story-title").value,slug:$("#story-slug").value,author:$("#story-author").value,description:$("#story-description").value,cover_url:$("#story-cover").value,story_type:$("#story-type").value,status:$("#story-status").value};
 const m=$("#form-message");message(m,"Đang lưu...");
 try{await api(id?"/api/admin/stories/"+id:"/api/admin/stories",{method:id?"PUT":"POST",body:JSON.stringify(payload)});message(m,"Đã lưu truyện thành công.");resetForm();await loadStories()}
 catch(err){message(m,err.message,true)}
});
async function loadStories(){
 const box=$("#story-list");box.innerHTML='<p class="hint">Đang tải danh sách...</p>';
 try{
  const r=await api("/api/admin/stories");
  if(!r.stories.length){box.innerHTML='<p class="hint">Chưa có truyện. Hãy thêm truyện đầu tiên ở biểu mẫu phía trên.</p>';return}
  box.innerHTML=r.stories.map(s=>'<article class="story-item">'+(s.cover_url?'<img src="'+escapeAttr(s.cover_url)+'" alt="">':'<div class="cover-placeholder"></div>')+'<div class="story-main"><strong>'+escapeHtml(s.title)+'</strong><div class="story-meta">'+escapeHtml(s.author||"Chưa rõ tác giả")+' · <span class="status">'+escapeHtml(s.status)+'</span></div></div><div class="story-actions"><button data-edit="'+s.id+'">Sửa</button><button data-delete="'+s.id+'">Xóa</button></div></article>').join("");
  box.querySelectorAll("[data-edit]").forEach(b=>b.onclick=()=>editStory(r.stories.find(s=>s.id===Number(b.dataset.edit))));
  box.querySelectorAll("[data-delete]").forEach(b=>b.onclick=()=>deleteStory(Number(b.dataset.delete)));
 }catch(err){box.innerHTML='<p class="message">'+escapeHtml(err.message)+'</p>'}
}
function editStory(s){if(!s)return;$("#story-id").value=s.id;$("#story-title").value=s.title;$("#story-slug").value=s.slug;$("#story-author").value=s.author||"";$("#story-description").value=s.description||"";$("#story-cover").value=s.cover_url||"";$("#story-type").value=s.story_type;$("#story-status").value=s.status;$("#form-title").textContent="Chỉnh sửa truyện";$("#save-story").textContent="Cập nhật";$("#cancel-edit").hidden=false;window.scrollTo({top:0,behavior:"smooth"})}
async function deleteStory(id){if(!confirm("Bạn chắc chắn muốn xóa truyện này và các chương liên quan?"))return;try{await api("/api/admin/stories/"+id,{method:"DELETE"});await loadStories()}catch(err){alert(err.message)}}
function escapeHtml(v){return String(v??"").replace(/[&<>"]/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;"}[c]))}
function escapeAttr(v){return escapeHtml(v).replace(/'/g,"&#39;")}
(async()=>{try{const r=await api("/api/auth/me");if(r.user&&r.user.role==="admin")showDashboard(r.user);else showLogin()}catch{showLogin()}})();