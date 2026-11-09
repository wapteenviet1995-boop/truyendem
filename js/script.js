var S=[
{n:"Bạch Dương",s:"♈",r:[3,21,4,19],e:"Lửa",desc:"Người tiên phong, thẳng thắn và đầy năng lượng. Bạch Dương thích bắt đầu trước, nghĩ kỹ sau.",love:"Chủ động, nồng nhiệt, ghét sự mập mờ. Cần người bạn đời theo kịp nhịp độ của mình.",job:"Hợp vị trí dẫn dắt, kinh doanh, thể thao, nơi cần quyết định nhanh."},
{n:"Kim Ngưu",s:"♉",r:[4,20,5,20],e:"Đất",desc:"Kiên nhẫn, thực tế và trân trọng sự ổn định. Kim Ngưu đã quyết là làm đến cùng.",love:"Chung thủy, chậm mà chắc, thể hiện tình cảm bằng hành động nhỏ mỗi ngày.",job:"Hợp tài chính, ẩm thực, thiết kế, những việc cần sự bền bỉ."},
{n:"Song Tử",s:"♊",r:[5,21,6,20],e:"Khí",desc:"Nhanh trí, tò mò và giao tiếp giỏi. Song Tử luôn muốn biết điều mới.",love:"Thích trò chuyện hợp gu, dễ chán nếu mối quan hệ lặp lại.",job:"Hợp truyền thông, viết lách, giảng dạy, bán hàng."},
{n:"Cự Giải",s:"♋",r:[6,21,7,22],e:"Nước",desc:"Nhạy cảm, giàu trực giác và coi trọng gia đình. Cự Giải bảo vệ người mình thương.",love:"Sâu sắc, chu đáo, cần cảm giác an toàn để mở lòng.",job:"Hợp chăm sóc sức khỏe, giáo dục, nhân sự, ẩm thực."},
{n:"Sư Tử",s:"♌",r:[7,23,8,22],e:"Lửa",desc:"Tự tin, hào phóng và có sức hút tự nhiên. Sư Tử thích được ghi nhận.",love:"Lãng mạn, rộng rãi, mong được trân trọng tương xứng.",job:"Hợp quản lý, sân khấu, sáng tạo nội dung, lãnh đạo nhóm."},
{n:"Xử Nữ",s:"♍",r:[8,23,9,22],e:"Đất",desc:"Tỉ mỉ, logic và luôn muốn mọi thứ tốt hơn. Xử Nữ để ý chi tiết người khác bỏ qua.",love:"Kín đáo, quan tâm qua việc thực tế, cần thời gian để tin tưởng.",job:"Hợp phân tích, y tế, biên tập, kế toán, kiểm định."},
{n:"Thiên Bình",s:"♎",r:[9,23,10,22],e:"Khí",desc:"Hòa nhã, công bằng và có gu thẩm mỹ. Thiên Bình ghét xung đột.",love:"Lịch thiệp, lãng mạn, đôi khi do dự khi phải chọn một người.",job:"Hợp luật, ngoại giao, thiết kế, tư vấn, nghệ thuật."},
{n:"Bọ Cạp",s:"♏",r:[10,23,11,21],e:"Nước",desc:"Mạnh mẽ, sâu sắc và quyết liệt. Bọ Cạp ít nói nhưng quan sát rất kỹ.",love:"Đam mê, trung thành tuyệt đối, đòi hỏi sự thành thật.",job:"Hợp nghiên cứu, điều tra, tâm lý, đầu tư, an ninh."},
{n:"Nhân Mã",s:"♐",r:[11,22,12,21],e:"Lửa",desc:"Lạc quan, yêu tự do và thích khám phá. Nhân Mã nói thật, đôi khi hơi thẳng.",love:"Vui vẻ, phóng khoáng, cần không gian riêng trong mối quan hệ.",job:"Hợp du lịch, giáo dục, xuất bản, kinh doanh quốc tế."},
{n:"Ma Kết",s:"♑",r:[12,22,1,19],e:"Đất",desc:"Kỷ luật, có trách nhiệm và kiên trì với mục tiêu dài hạn.",love:"Nghiêm túc, chậm nhưng bền, thể hiện tình cảm qua sự đáng tin cậy.",job:"Hợp quản trị, kỹ thuật, tài chính, xây dựng sự nghiệp bài bản."},
{n:"Bảo Bình",s:"♒",r:[1,20,2,18],e:"Khí",desc:"Độc lập, sáng tạo và nghĩ khác số đông. Bảo Bình quan tâm đến cộng đồng.",love:"Cần tình bạn làm nền, thích người có chính kiến riêng.",job:"Hợp công nghệ, khoa học, hoạt động xã hội, sáng tạo."},
{n:"Song Ngư",s:"♓",r:[2,19,3,20],e:"Nước",desc:"Mơ mộng, giàu cảm xúc và đồng cảm. Song Ngư có trí tưởng tượng phong phú.",love:"Dịu dàng, hy sinh, cần người hiểu và không làm tổn thương.",job:"Hợp nghệ thuật, âm nhạc, tâm lý, chăm sóc, công việc thiện nguyện."}
];
var OV=["Năng lượng đang lên, hãy bắt đầu việc bạn hoãn từ lâu.","Một tin nhắn cũ có thể mở ra cơ hội mới.","Hôm nay hợp để chậm lại và sắp xếp lại ưu tiên.","Quyết định nhỏ hôm nay sẽ giúp bạn nhẹ đầu hơn vào cuối tuần.","Đừng ngại nhờ giúp, người xung quanh sẵn lòng hơn bạn nghĩ.","Trực giác của bạn khá chính xác, hãy tin nó ở chuyện quan trọng."];
var LV=["Nói rõ điều bạn cần, đối phương sẽ dễ hiểu hơn.","Một buổi cà phê đơn giản tốt hơn kế hoạch cầu kỳ.","Người độc thân có thể gặp ai đó qua bạn bè.","Lắng nghe nhiều hơn một chút sẽ hóa giải hiểu lầm.","Dành thời gian cho người thân sẽ giúp bạn cân bằng."];
var WK=["Tập trung một việc quan trọng thay vì làm nhiều việc cùng lúc.","Hạn chót cần được nhắc lại, hãy xác nhận với đồng nghiệp.","Ý tưởng của bạn được chú ý, hãy trình bày ngắn gọn.","Tránh chi tiêu bốc đồng, hãy đợi thêm một ngày.","Học một kỹ năng nhỏ sẽ có ích hơn bạn nghĩ."];
var CL=["đỏ","xanh lam","vàng","tím","xanh lá","trắng"];

var $=function(i){return document.getElementById(i)};
var cur=-1;

function hash(str){var h=2166136261;for(var i=0;i<str.length;i++){h^=str.charCodeAt(i);h=Math.imul(h,16777619)}return h>>>0}
function pick(a,k){return a[hash(k)%a.length]}

function signOf(m,d){
  var v=m*100+d;
  for(var i=0;i<S.length;i++){
    var r=S[i].r,a=r[0]*100+r[1],b=r[2]*100+r[3];
    if(a<=b?(v>=a&&v<=b):(v>=a||v<=b))return i;
  }
}
function range(i){var r=S[i].r;return r[1]+"/"+r[0]+" – "+r[3]+"/"+r[2]}
function esc(t){return String(t).replace(/[&<>"]/g,function(c){return{"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;"}[c]})}

function sector(i){
  var a0=(-90+i*30+0.6)*Math.PI/180,a1=(-90+(i+1)*30-0.6)*Math.PI/180,R=192,r=104,c=200;
  var p=function(rad,a){return (c+rad*Math.cos(a)).toFixed(1)+" "+(c+rad*Math.sin(a)).toFixed(1)};
  return "M"+p(R,a0)+" A"+R+" "+R+" 0 0 1 "+p(R,a1)+" L"+p(r,a1)+" A"+r+" "+r+" 0 0 0 "+p(r,a0)+" Z";
}
function buildWheel(){
  var g="";
  S.forEach(function(z,i){
    var am=(-90+i*30+15)*Math.PI/180,x=200+148*Math.cos(am),y=200+148*Math.sin(am);
    g+='<g class="sec" id="sec'+i+'" role="button" tabindex="0" aria-label="'+z.n+', '+range(i)+'" data-i="'+i+'"><path d="'+sector(i)+'"/><text x="'+x.toFixed(1)+'" y="'+y.toFixed(1)+'">'+z.s+'</text></g>';
  });
  g+='<g id="hub"></g>';
  $("wheel").innerHTML=g;
  $("wheel").addEventListener("click",function(e){var t=e.target.closest(".sec");if(t)show(+t.dataset.i)});
  $("wheel").addEventListener("keydown",function(e){
    if(e.key==="Enter"||e.key===" "){var t=e.target.closest(".sec");if(t){e.preventDefault();show(+t.dataset.i)}}
  });
}
function hub(i){
  $("hub").innerHTML=i<0
    ?'<text class="hub-d" x="200" y="196">Chọn một cung</text>'
    :'<text class="hub-s" x="200" y="190">'+S[i].s+'</text><text class="hub-n" x="200" y="222">'+S[i].n+'</text><text class="hub-d" x="200" y="244">'+range(i)+'</text>';
}
function show(i){
  if(cur>=0)$("sec"+cur).classList.remove("on");
  cur=i;$("sec"+i).classList.add("on");hub(i);
  var z=S[i],t=new Date(),ds=t.getFullYear()+"-"+(t.getMonth()+1)+"-"+t.getDate(),k=z.n+ds;
  var vn=t.getDate()+"/"+(t.getMonth()+1)+"/"+t.getFullYear();
  $("detail").innerHTML=
   '<h2>'+z.s+' '+esc(z.n)+'</h2>'+
   '<p class="meta">'+range(i)+' · Nguyên tố <b>'+z.e+'</b></p>'+
   '<p>'+esc(z.desc)+'</p>'+
   '<h3>Tình yêu</h3><p>'+esc(z.love)+'</p>'+
   '<h3>Sự nghiệp</h3><p>'+esc(z.job)+'</p>'+
   matchHtml(i)+'<div class="today"><h3>Hôm nay, '+vn+'</h3><dl>'+
   '<dt>Tổng quan</dt><dd>'+pick(OV,k+"o")+'</dd>'+
   '<dt>Tình cảm</dt><dd>'+pick(LV,k+"l")+'</dd>'+
   '<dt>Công việc</dt><dd>'+pick(WK,k+"w")+'</dd>'+
   '<dt>Con số may mắn</dt><dd>'+(hash(k+"n")%99+1)+'</dd>'+
   '<dt>Màu may mắn</dt><dd>'+pick(CL,k+"c")+'</dd></dl>'+
   '<p class="note">Nội dung demo, được tạo tự động theo ngày. Bản PHP sẽ đọc từ bảng horoscopes.</p></div>';
}

var KEY="zodiac_birth_v1",today0=dkey();
function dkey(){var t=new Date();return t.getFullYear()+"-"+t.getMonth()+"-"+t.getDate()}
function store(op,v){try{if(op==="get")return localStorage.getItem(KEY);if(op==="set")localStorage.setItem(KEY,v);else localStorage.removeItem(KEY)}catch(e){return null}}
function save(dd,mm){store("set",dd+"/"+mm);renderSaved()}
function renderSaved(){
  var v=store("get"),el=$("saved");
  if(!v){el.innerHTML="";return}
  var p=v.split("/"),dd=+p[0],mm=+p[1],t=new Date(),y=t.getFullYear(),t0=new Date(y,t.getMonth(),t.getDate()),b=new Date(y,mm-1,dd);
  if(b<t0)b=new Date(y+1,mm-1,dd);
  var days=Math.round((b-t0)/864e5);
  el.innerHTML="Đã nhớ ngày sinh "+dd+"/"+mm+" · "+(days===0?"Hôm nay là sinh nhật bạn!":"Còn "+days+" ngày đến sinh nhật")+' <button class="lnk" id="forget">Xóa</button>';
  $("forget").onclick=function(){store("del");renderSaved()};
}
function autoLoad(){
  var v=store("get");renderSaved();
  if(!v)return;
  var p=v.split("/"),dd=+p[0],mm=+p[1];
  $("d").value=dd;$("m").value=mm;show(signOf(mm,dd));
}
function sunBar(){
  var t=new Date(),y=t.getFullYear(),t0=new Date(y,t.getMonth(),t.getDate()),i=signOf(t.getMonth()+1,t.getDate()),n=(i+1)%12,r=S[n].r,nd=new Date(y,r[0]-1,r[1]);
  if(nd<=t0)nd=new Date(y+1,r[0]-1,r[1]);
  var days=Math.round((nd-t0)/864e5);
  $("sun").innerHTML='Hôm nay Mặt Trời ở cung <button class="chip" id="chip">'+S[i].s+" "+S[i].n+"</button>. Còn "+days+" ngày nữa sang cung "+S[n].n+".";
  $("chip").onclick=function(){show(i)};
}
var EL={"Lửa":"Khí","Khí":"Lửa","Đất":"Nước","Nước":"Đất"};
function score(a,b){
  var x=S[a].e,y=S[b].e,s;
  if(x===y)s=84;else if(EL[x]===y)s=90;else if((x==="Lửa"&&y==="Đất")||(x==="Đất"&&y==="Lửa"))s=56;else s=62;
  return s+hash("p"+Math.min(a,b)+"-"+Math.max(a,b))%9-4;
}
function matchHtml(i){
  var l=[];
  for(var j=0;j<12;j++)if(j!==i)l.push([j,score(i,j)]);
  l.sort(function(a,b){return b[1]-a[1]});
  var low=l[l.length-1],f=function(p){return "<li>"+S[p[0]].s+" "+S[p[0]].n+" <b>"+p[1]+"%</b></li>"};
  return '<h3>Hợp nhất với</h3><ul class="match">'+l.slice(0,3).map(f).join("")+'</ul><p class="note">Ít hợp nhất: '+S[low[0]].s+" "+S[low[0]].n+" ("+low[1]+"%). Điểm tính tự động từ nguyên tố, chỉ mang tính tham khảo.</p>";
}
function tick(){
  var k=dkey();
  if(k!==today0){today0=k;sunBar();renderSaved();if(cur>=0)show(cur)}
}
function startClock(){setInterval(tick,60000);document.addEventListener("visibilitychange",tick)}
(function init(){
  var d=$("d"),m=$("m");
  for(var i=1;i<=31;i++)d.add(new Option(i,i));
  for(var j=1;j<=12;j++)m.add(new Option("Tháng "+j,j));
  buildWheel();hub(-1);sunBar();
  $("detail").innerHTML='<p class="meta">Chưa chọn cung nào.</p><p>Nhập ngày sinh ở trên hoặc chạm vào một cung trên vòng tròn.</p>';
  autoLoad();startClock();
  $("go").addEventListener("click",function(){
    var dd=+d.value,mm=+m.value,max=[31,29,31,30,31,30,31,31,30,31,30,31][mm-1];
    if(dd>max){$("err").textContent="Tháng "+mm+" chỉ có tối đa "+max+" ngày. Hãy chọn lại ngày.";return}
    $("err").textContent="";
    save(dd,mm);show(signOf(mm,dd));
    $("detail").scrollIntoView({behavior:window.matchMedia("(prefers-reduced-motion: reduce)").matches?"auto":"smooth",block:"nearest"});
  });
})();
