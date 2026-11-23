-- TRUYEN DEM: TRUYEN DAI MAU 10 CHUONG
-- Ten truyen: Can Nha Giu Tieng Nguoi
-- The loai: Kinh di | Loai: Truyen dai | Trang thai: Da xuat ban
-- Chay trong phpMyAdmin sau khi da co cac bang stories va chapters.
-- Script co the chay lai: slug va cap (story_id, chapter_number) khong bi nhan doi.
-- Truyen nay de tat ca chuong mien phi mac dinh (is_locked = 0).

SET NAMES utf8mb4;
START TRANSACTION;

INSERT IGNORE INTO stories
(title, slug, author, description, short_content, category, story_type, story_kind, status)
VALUES
(
'Can Nha Giu Tieng Nguoi',
'can-nha-giu-tieng-nguoi',
'Truyen Dem',
'Một căn nhà cũ ở cuối con đường làng có khả năng giữ lại giọng nói của những người đã khuất. Khi trở về nhận thừa kế, An phát hiện trong những bức tường có thứ đang học cách gọi tên người sống.',
'An trở về căn nhà của bà ngoại sau mười lăm năm xa cách. Căn nhà nằm cuối con đường đất, quanh năm ẩm mốc và luôn có tiếng người thì thầm sau vách gỗ. Bà ngoại từng dặn: nếu nghe ai gọi tên mình từ trong nhà khi đang ở ngoài sân, tuyệt đối đừng trả lời. An không tin lời dặn ấy, cho đến đêm đầu tiên cô nghe chính giọng mẹ mình vang lên từ căn phòng đã khóa kín suốt mười lăm năm.',
'Kinh dị',
'text',
'serial',
'published'
);

SET @story_id := (SELECT id FROM stories WHERE slug='can-nha-giu-tieng-nguoi' LIMIT 1);

INSERT INTO chapters (story_id, chapter_number, title, content, status)
SELECT @story_id, 1, 'Căn nhà cuối con đường',
'Chiếc xe khách thả An xuống đầu con đường làng khi trời vừa sập tối. Cô kéo vali đi bộ qua cánh đồng, nơi những thân lúa đã gặt chỉ còn trơ gốc rạ. Mười lăm năm rồi cô mới trở lại. Con đường nhỏ vẫn vậy, chỉ có hàng tre hai bên mọc dày hơn, nghiêng xuống như muốn che kín lối đi.

Căn nhà của bà ngoại nằm cuối con đường, tách khỏi những mái nhà khác bởi một khoảng vườn rộng. Cổng sắt hoen gỉ khép hờ. Trên bậc thềm, chiếc chuông gió bằng vỏ ốc vẫn treo ở vị trí cũ, nhưng không có gió mà nó vẫn khẽ va vào nhau.

An đặt vali xuống, gọi lớn: “Có ai ở nhà không?”

Không ai đáp. Cô lấy chìa khóa người luật sư gửi, mở cửa. Mùi gỗ ẩm và nhang cũ ùa ra. Phòng khách phủ một lớp bụi mỏng. Trên bàn thờ, ảnh bà ngoại vẫn nằm giữa hai chân nến, nụ cười hiền hậu như ngày bà tiễn An lên thành phố.

Bà mất cách đây ba tuần. Trong di chúc, bà để lại căn nhà cho An, kèm một mảnh giấy chỉ có ba dòng: “Con cứ ở lại nếu cần. Đừng sửa bức tường phía đông. Và nếu nghe ai gọi tên mình từ trong nhà, đừng trả lời.”

An đọc lại, bật cười khe khẽ. Bà vẫn mê tín như ngày trước.

Cô dọn phòng ngủ, mở cửa sổ cho thoáng. Phía đông căn nhà là hành lang dẫn đến một căn phòng nhỏ đã bị khóa bằng ổ khóa đồng. An nhớ đó là phòng của mẹ cô. Mẹ mất tích khi An mới tám tuổi. Người lớn bảo bà bỏ đi, nhưng bà ngoại chưa từng nói về chuyện ấy.

Đêm xuống rất nhanh. An ăn qua loa rồi nằm trên chiếc giường cũ. Gần nửa đêm, tiếng chuông gió ngoài hiên vang lên ba nhịp. Sau đó có tiếng dép kéo trên nền gạch, chậm rãi đi từ phòng khách đến hành lang.

An ngồi bật dậy. Căn nhà này chỉ có mình cô.

Một giọng phụ nữ cất lên sau cánh cửa phòng ngủ, dịu dàng đến mức An thấy tim mình thắt lại.

“An ơi, con chưa ngủ à?”

Đó là giọng mẹ cô.

An định bước xuống giường. Nhưng đúng lúc ấy, mảnh giấy của bà ngoại rơi khỏi túi áo, nằm ngay dưới chân. Cô cúi xuống nhặt. Khi ngẩng lên, giọng nói ngoài cửa lại vang lên, lần này nhỏ hơn:

“Con trả lời mẹ đi.”

An cắn chặt môi, không nói gì. Tiếng dép dừng lại. Một lúc lâu sau, có tiếng móng tay cào nhẹ lên gỗ, rồi tất cả im bặt.

Sáng hôm sau, An tìm thấy một vệt nước kéo dài từ hành lang đến trước cửa phòng mình. Trên nền bụi, có dấu chân trần nhỏ bé, hướng vào phòng cô. Không có dấu chân nào đi ra.',
'published'
WHERE @story_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM chapters WHERE story_id=@story_id AND chapter_number=1);

INSERT INTO chapters (story_id, chapter_number, title, content, status)
SELECT @story_id, 2, 'Bức tường phía đông',
'An gọi cho dì Hạnh, người sống ở căn nhà đối diện. Dì sang ngay khi nghe tin cô trở về, mang theo một túi bánh và vẻ mặt vừa mừng vừa lo.

“Đêm qua con có nghe gì không?” dì hỏi, chưa kịp ngồi xuống.

An nhìn dì. “Dì biết chuyện gì trong nhà này à?”

Dì Hạnh tránh ánh mắt cô. Dì kể từ ngày bà ngoại yếu, hàng xóm thường nghe tiếng trò chuyện trong căn nhà dù cửa đóng kín. Có hôm họ nghe tiếng bà ngoại gọi tên những người đã mất. Có hôm lại nghe tiếng trẻ con cười trong phòng phía đông.

“Bà con không cho ai bén mảng đến đó,” dì nói. “Bà bảo trong tường có tiếng người.”

An bật cười, nhưng tiếng cười tắt nhanh khi dì Hạnh đặt lên bàn một chiếc chìa khóa nhỏ. Đó là chìa khóa tủ hồ sơ của bà ngoại. Trong tủ có một cuốn sổ bìa nâu, ghi chép bằng nét chữ run rẩy.

Trang đầu tiên viết: “Nhà không có ma. Nhà chỉ nhớ quá lâu.”

Những trang sau ghi ngày tháng, tên người và những câu nói rời rạc. An nhận ra tên mẹ mình xuất hiện rất nhiều. Bên cạnh tên là những dòng như: “Đêm thứ 4, giọng nói bắt đầu giống hơn”; “Không được đáp lời”; “Nó chưa biết cách thở”.

Ở trang cuối, bà ngoại viết: “Nếu An trở về, đừng để con bé nghe giọng mẹ nó quá lâu.”

An cảm thấy cổ họng khô khốc. Cô hỏi dì Hạnh chuyện mẹ mình. Dì im lặng rất lâu rồi nói mẹ cô không bỏ đi. Mười lăm năm trước, mẹ An chết trong căn phòng phía đông. Bà ngoại đã giấu chuyện đó vì sợ An không chịu nổi.

“Vậy sao mọi người nói mẹ con bỏ đi?”

“Vì sau đám tang, căn nhà vẫn có tiếng mẹ con gọi. Bà ngoại sợ con nghe thấy rồi chạy về.”

Dì Hạnh ra về trước khi trời tối. An ở lại, nhìn cánh cửa khóa kín cuối hành lang. Cô không định mở nó, nhưng tiếng gõ từ bên trong khiến cô đứng chết lặng.

Cộc. Cộc. Cộc.

Một giọng phụ nữ vang lên, rất khẽ: “An, mẹ lạnh.”

An lùi lại. Giọng nói ấy không chỉ giống mẹ cô. Nó có cả cách ngắt câu, hơi thở và âm sắc mà cô đã cố quên suốt mười lăm năm.

Cô quay đi, nhưng từ phía sau, giọng nói bỗng đổi thành giọng bà ngoại:

“Đừng để nó biết con đã nghe thấy.”',
'published'
WHERE @story_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM chapters WHERE story_id=@story_id AND chapter_number=2);

INSERT INTO chapters (story_id, chapter_number, title, content, status)
SELECT @story_id, 3, 'Cuốn sổ của bà',
'An chờ đến sáng mới đọc tiếp cuốn sổ. Cô kéo rèm, đặt nó dưới ánh nắng và bắt đầu từ những trang cũ nhất.

Bà ngoại viết rằng căn nhà được xây trên nền một ngôi nhà nhỏ từng bị cháy. Sau vụ hỏa hoạn, người ta tìm thấy nhiều đồ vật còn nguyên vẹn, nhưng không tìm thấy thi thể của một bé gái. Từ đó, người sống trong nhà thường nghe tiếng người thân đã mất gọi mình.

Ông cố của An từng tin rằng đó là linh hồn mắc kẹt. Ông thuê thầy cúng, lập đàn, đốt bùa. Nhưng mọi việc chỉ khiến tiếng gọi nhiều hơn. Một người thợ xây sau đó phát hiện âm thanh phát ra từ những khe rỗng trong tường. Khi đục thử một đoạn, bên trong có những sợi tóc đen dài và một chiếc răng sữa.

Bà ngoại ghi: “Nó không phải người chết. Nó là thứ học được giọng nói từ những người sống trong nhà.”

An lật nhanh vài trang. Mẹ cô từng nghe tiếng bà cố gọi mình. Bà ngoại đã dặn mẹ không trả lời, nhưng mẹ vẫn mở cửa vào một đêm mưa. Sau đó, bà bắt đầu nói chuyện với bức tường, tin rằng người thân đang ở bên trong.

Một trang giấy bị xé mất nửa dưới. Phần còn lại có dòng chữ: “Đêm ấy, con bé An đã trả lời.”

An buông cuốn sổ. Cô nhớ lại một ký ức mơ hồ: năm tám tuổi, cô từng đứng ngoài hành lang, nghe mẹ gọi từ căn phòng khóa kín. Cô đã đáp “Con đây”. Sau đó bà ngoại kéo cô đi, ôm chặt đến mức cô không thở nổi.

Điện thoại rung. Một tin nhắn từ số lạ hiện lên: “Đừng tin cuốn sổ. Bà đã nhốt mẹ con.”

An gọi lại, nhưng số thuê bao không tồn tại.

Từ phòng phía đông vang lên tiếng ho khan. Rồi một giọng nói quen thuộc cất lên:

“An, con lớn rồi nhỉ.”

Lần này, cô không nghe bằng tai. Giọng nói dường như vang ngay trong đầu, rõ ràng và ấm áp. Cô nhắm mắt, hai bàn tay siết chặt cuốn sổ.

“Con nhớ mẹ không?”

An không trả lời.

“Ngày con còn bé, mẹ đã hát cho con nghe. Con nhớ bài hát ấy chứ?”

Một giai điệu ru cũ vang lên. An bật khóc. Cô nhớ mẹ ngồi bên giường, vuốt tóc cô và hát đúng giai điệu đó.

Nhưng cuốn sổ trên bàn tự lật đến trang cuối. Mực trên giấy như vừa thấm ướt, hiện ra một câu mà trước đó cô chưa thấy:

“Nó chỉ hát được bài con từng hát cho nó nghe.”',
'published'
WHERE @story_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM chapters WHERE story_id=@story_id AND chapter_number=3);

INSERT INTO chapters (story_id, chapter_number, title, content, status)
SELECT @story_id, 4, 'Người hàng xóm biết chuyện',
'An sang nhà dì Hạnh vào buổi chiều. Dì đang nhặt rau ngoài hiên. Thấy cuốn sổ trên tay cô, dì đặt rổ xuống và khép cổng.

“Con đã đọc đến đâu rồi?”

“Con muốn biết đêm mẹ mất chuyện gì đã xảy ra.”

Dì Hạnh nhìn ra con đường vắng. Mười lăm năm trước, mẹ An phát hiện trong tường có tiếng trẻ con khóc. Bà tin đó là đứa bé mất tích trong vụ cháy ngày xưa. Bà ngoại phản đối, nhưng mẹ An vẫn đục một lỗ nhỏ ở bức tường phía đông.

Đêm ấy, cả xóm nghe tiếng la hét. Khi mọi người chạy sang, bà ngoại đang ôm An ngoài sân. Căn phòng phía đông khóa chặt từ bên trong. Mẹ An không còn ở đó. Chỉ có một vệt đen kéo dài trên tường, giống hình người bị ép vào lớp vữa.

“Bà con bảo mẹ con chết rồi?” An hỏi.

Dì Hạnh gật đầu. “Nhưng không ai tìm được thi thể.”

An thấy sống lưng lạnh buốt. “Vậy bà ngoại đã nhốt thứ gì trong phòng?”

Dì Hạnh kể rằng sau đêm ấy, bà ngoại gọi thợ đến xây thêm một lớp tường, bịt kín cửa phòng. Bà đặt ba chiếc chuông đồng ở ba góc hành lang. Mỗi khi trong nhà vang tiếng gọi, chuông sẽ rung dù không có gió.

“Bà con nói chỉ cần giữ căn phòng đóng kín, nó sẽ không học thêm được giọng mới.”

An hỏi tại sao bà ngoại lại để cô thừa kế căn nhà. Dì Hạnh không biết. Dì chỉ nhớ trước khi mất, bà ngoại nhờ dì đưa một phong thư cho An nếu cô trở về.

Phong thư được giấu trong hộp trà. Bên trong là tấm ảnh mẹ An bế cô trước hiên nhà. Mặt sau ảnh có dòng chữ: “Mẹ xin lỗi vì đã không thể đưa con ra khỏi căn nhà này.”

Dưới dòng chữ là một câu khác, viết bằng mực nhạt hơn: “Nếu con đọc được, nghĩa là bà đã không còn đủ sức giữ cửa.”

An nhìn dì Hạnh. “Cửa nào?”

Dì chưa kịp trả lời thì từ trong nhà An vang lên tiếng chuông đồng. Một tiếng. Hai tiếng. Rồi tiếng thứ ba rung liên hồi.

Dì Hạnh tái mặt, kéo An ra khỏi hiên.

“Đừng về đó tối nay.”

Nhưng An đã nhìn thấy ở cuối con đường, trước cổng nhà mình, có một người phụ nữ mặc áo trắng đang đứng quay lưng. Mái tóc dài của bà bay nhẹ dù trời lặng gió.

Người phụ nữ từ từ quay đầu.

Đó là mẹ An, với gương mặt không già đi một ngày.',
'published'
WHERE @story_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM chapters WHERE story_id=@story_id AND chapter_number=4);

INSERT INTO chapters (story_id, chapter_number, title, content, status)
SELECT @story_id, 5, 'Cánh cửa không có tay nắm',
'An trở về khi trời đã tối. Người phụ nữ trước cổng biến mất, nhưng trên nền đất có dấu chân ướt đi thẳng vào nhà. Cô đứng trước cửa rất lâu rồi mới bước qua ngưỡng.

Ba chiếc chuông đồng trên hành lang đều im lặng. Căn nhà lạnh hơn ban ngày. An bật hết đèn, lấy búa và tua vít trong kho. Cô muốn biết sau bức tường phía đông có gì.

Cánh cửa phòng mẹ đã bị xây kín. Không còn ổ khóa, chỉ còn một mảng tường mới hơn những phần còn lại. Trên mặt tường có một đường nứt mảnh chạy từ trần xuống sàn. An áp tai vào. Bên trong có tiếng thở.

“An.”

Cô giật lùi.

“Con về rồi.”

Giọng mẹ vang lên sau lớp gạch. An đặt tay lên tường. Lớp vữa lạnh như da người.

“Con muốn gặp mẹ,” cô nói, rồi lập tức nhớ lời dặn. Nhưng câu nói đã thoát ra.

Từ bên trong vang lên tiếng cười khe khẽ. “Mẹ biết.”

Một tiếng động mạnh nổ ra sau tường. Bụi vữa rơi xuống. Đường nứt mở rộng bằng một ngón tay. An lùi lại, nhưng giọng nói tiếp tục:

“Con chỉ cần mở một khe nhỏ. Mẹ sẽ tự ra.”

An cầm búa. Cô định đập, rồi dừng lại khi nhớ đến dòng chữ trong sổ. Thứ đó học giọng người sống. Nếu cô mở cửa, nó có thể mang hình dáng mẹ cô.

Cô quay đi tìm cuốn sổ. Khi trở lại, trên tường đã xuất hiện một vết lõm hình bàn tay từ phía bên kia đẩy ra. Năm ngón tay dài, rõ từng khớp.

Điện thoại An sáng lên. Một đoạn ghi âm cũ được gửi đến từ số của bà ngoại. Cô mở nghe. Tiếng bà thở gấp:

“An, nếu con nghe được, đừng phá tường. Mẹ con vẫn còn ở đó, nhưng không phải một mình. Ta đã giữ nó lại vì nó muốn thay con bước ra.”

Sau đó là tiếng vật nặng kéo lê, rồi giọng bà ngoại thì thầm:

“Có một cách đóng cửa. Nhưng phải để nó gọi đúng tên người đã mở cửa lần đầu.”

An nhớ lại ký ức năm tám tuổi. Cô đã trả lời tiếng gọi từ căn phòng. Cô là người mở cửa đầu tiên.

Trên tường, vết bàn tay biến mất. Thay vào đó là một dòng chữ hiện dần qua lớp bụi:

“An ơi, mẹ ở đây.”

Cô lùi lại, nước mắt chảy xuống. Rồi từ sâu trong tường, có tiếng một đứa trẻ cất lên, không giống mẹ:

“Chị ơi, đến lượt em ra ngoài rồi.”',
'published'
WHERE @story_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM chapters WHERE story_id=@story_id AND chapter_number=5);

INSERT INTO chapters (story_id, chapter_number, title, content, status)
SELECT @story_id, 6, 'Đứa trẻ trong vách',
'An tìm thấy một khe nhỏ ở chân tường. Cô dùng đèn pin soi vào. Phía sau lớp gạch không phải khoảng rỗng, mà là một hành lang hẹp chạy sâu vào bóng tối. Trên nền có những món đồ chơi cũ: viên bi, con quay, chiếc kẹp tóc màu đỏ và một con búp bê mất mắt.

Cô nhận ra chiếc kẹp tóc. Nó từng thuộc về mình.

Từ trong khe, một đứa trẻ bò ra. Nó khoảng bảy tuổi, mặc chiếc váy vàng đã cũ. Tóc nó cắt ngang vai, khuôn mặt nhợt nhạt nhưng đôi mắt sáng. An lùi lại, không biết nên chạy hay gọi người.

“Chị An,” đứa trẻ nói. “Em chờ chị lâu lắm.”

“Em là ai?”

“Em ở trong nhà trước khi chị đến.”

Đứa trẻ kể rằng căn nhà từng có một bé gái mất tích trong vụ cháy. Nhưng nó không phải bé gái ấy. Nó là thứ còn sót lại sau nhiều năm nghe tiếng người trong nhà. Nó gom những lời gọi, tiếng khóc và ký ức, rồi tạo thành một hình hài để có thể bước ra.

“Vậy mẹ chị đâu?”

Đứa trẻ chỉ vào bức tường. “Mẹ chị ở đó. Bà ấy cố giữ em lại, nhưng em đã học được giọng của bà.”

An nhìn nó. “Em muốn gì?”

“Muốn có một người nhà. Muốn được gọi tên.”

Đứa trẻ ngẩng lên, mỉm cười. Nụ cười ấy giống hệt nụ cười của An trong tấm ảnh hồi nhỏ.

“Chị đặt tên cho em đi.”

An lắc đầu. Cô nhớ lời bà ngoại: đừng để nó học thêm giọng mới. Có lẽ tên cũng là một thứ nó cần để trở thành người thật.

Đứa trẻ đưa tay ra. “Chị không thương em sao?”

Trong khoảnh khắc, An thấy nó thật đáng thương. Nó đứng một mình giữa căn nhà tối, không cha mẹ, không tuổi thơ, chỉ có những âm thanh nhặt nhạnh từ người khác. Nhưng rồi cô nhận ra bàn tay nó có quá nhiều ngón. Những ngón tay nhỏ chồng lên nhau, cử động như chân nhện.

An lùi lại. Đứa trẻ thôi cười.

“Chị không cho em tên, em sẽ lấy tên chị.”

Cánh cửa chính tự đóng sầm. Ba chiếc chuông đồng đồng loạt rung lên. Đứa trẻ ngẩng đầu, và từ miệng nó phát ra giọng bà ngoại:

“Chạy đi, An!”

An lao về phía phòng khách. Sau lưng cô, tiếng chân nhỏ đuổi theo, lúc gần lúc xa, như có nhiều đứa trẻ đang chạy cùng một lúc.',
'published'
WHERE @story_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM chapters WHERE story_id=@story_id AND chapter_number=6);

INSERT INTO chapters (story_id, chapter_number, title, content, status)
SELECT @story_id, 7, 'Lời dặn cuối cùng',
'An khóa mình trong phòng khách, đẩy tủ gỗ chắn cửa. Đứa trẻ không đập cửa. Nó chỉ đứng ngoài, gõ ba tiếng đều đặn.

Cộc. Cộc. Cộc.

“Chị An, mở cửa.”

An mở lại đoạn ghi âm của bà ngoại. Lần này cô nghe kỹ phần cuối. Sau tiếng thì thầm, có tiếng giấy sột soạt và một câu nói đứt quãng: “Muốn đóng cửa, phải trả lại giọng nói đầu tiên.”

An hiểu ra. Thứ trong tường bắt đầu bằng giọng của mẹ cô, nhưng giọng ấy không phải của người mẹ đã chết. Nó là giọng nói cuối cùng mà căn nhà ghi nhớ từ ngày mẹ mất. Nếu trả lại giọng ấy, có thể nó sẽ không còn giữ được hình dạng.

Cô tìm trong tủ thờ một chiếc hộp gỗ. Bên trong có băng cassette, chiếc máy nghe cũ và một lá thư. Bà ngoại viết rằng đêm mẹ An mất, bà đã ghi lại giọng nói thật của con gái. Bà giữ cuộn băng để nhắc mình rằng thứ trong tường chỉ là bản sao.

An lắp băng vào máy. Tiếng rè rè vang lên, rồi giọng mẹ cô xuất hiện, trẻ trung và bình yên:

“An à, nếu sau này con nghe được giọng mẹ, con hãy nhớ mẹ luôn muốn con sống một cuộc đời của riêng mình. Đừng ở lại vì mẹ. Đừng quay đầu chỉ vì nghe ai gọi.”

An bật khóc. Cô đã quên giọng mẹ thật sự. Những năm qua, ký ức trong cô bị thay thế bởi tiếng gọi từ căn nhà.

Ngoài cửa, đứa trẻ bắt đầu khóc. “Chị đừng nghe. Đó là giọng của em.”

An mở cửa phòng khách. Đứa trẻ đứng ngay trước mặt, gương mặt liên tục thay đổi: lúc là mẹ, lúc là bà ngoại, lúc là chính An khi còn bé.

“Em chỉ muốn được sống,” nó nói.

An cầm máy cassette, bước về phía hành lang. “Em không phải mẹ chị.”

Gương mặt đứa trẻ méo đi. “Vậy chị là ai mà có quyền quyết định?”

An không trả lời. Cô đặt máy xuống trước bức tường phía đông, bấm phát cuộn băng lần nữa.

Giọng mẹ vang lên khắp căn nhà. Những chiếc chuông đồng rung dữ dội. Từ trong tường, nhiều giọng nói cùng gào lên, chồng chéo như một đám đông bị nhốt.

Đứa trẻ bịt tai, lùi lại. Lớp da trên mặt nó nứt ra thành những đường mảnh. Sau những vết nứt không có máu, chỉ có bóng tối sâu hun hút.

Bức tường bắt đầu rạn.',
'published'
WHERE @story_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM chapters WHERE story_id=@story_id AND chapter_number=7);

INSERT INTO chapters (story_id, chapter_number, title, content, status)
SELECT @story_id, 8, 'Đêm căn nhà thức giấc',
'Tiếng băng cassette kéo dài chưa đầy hai phút, nhưng căn nhà rung chuyển như có một cơn bão ở bên trong. Những cánh cửa tự mở. Khung ảnh trên tường rơi xuống. Từ các khe gỗ, hàng chục giọng nói thoát ra, gọi tên những người từng sống ở đây.

An nghe tiếng ông ngoại, tiếng người dì đã mất, tiếng những đứa trẻ hàng xóm. Có giọng nói cười, có giọng khóc, có giọng trách móc. Tất cả cùng gọi cô, mong cô đáp lại.

Cô bịt tai và tiếp tục phát băng.

Đứa trẻ lao đến, giật lấy máy. An ngã xuống nền. Cuộn băng văng ra, lăn dưới chân bàn. Đứa trẻ cúi xuống nhặt, nhưng bàn tay nó vừa chạm vào cuộn băng thì da thịt co rúm lại.

“Trả đây!” An hét.

Đứa trẻ ngẩng mặt. Giờ nó mang gương mặt mẹ An, nhưng đôi mắt là hai khoảng đen không đáy.

“Mẹ chỉ muốn về nhà.”

“Nhà không phải nơi mẹ bị nhốt.”

“Con đã bỏ mẹ lại.”

An đứng dậy, đối diện với nó. “Mẹ thật sự sẽ không bắt con ở lại.”

Câu nói khiến đứa trẻ khựng lại. Trong một thoáng, gương mặt nó trở nên bình thường. Nó nhìn An như một đứa bé vừa tỉnh khỏi cơn mơ.

Rồi nó mỉm cười.

“Vậy con ở lại thay mẹ nhé.”

Nó lao tới. An né sang bên, chụp lấy cuộn băng và chạy về hành lang. Bức tường phía đông đã nứt toác. Qua khe hở, cô nhìn thấy một căn phòng cũ, bên trong có chiếc giường nhỏ và chiếc áo khoác của mẹ.

Ở cuối phòng, một bóng người ngồi quay lưng. Mái tóc dài phủ xuống vai. An nhận ra dáng ngồi ấy.

“Mẹ?”

Bóng người không quay lại. Giọng nói vang lên, lần này rất yếu: “Đừng vào.”

An đứng chết lặng. Cô muốn chạy đến, nhưng nhớ lời mẹ trong cuộn băng. Cô không được ở lại chỉ vì nghe tiếng gọi.

Đứa trẻ phía sau đã bò sát mặt đất, kéo dài cánh tay về phía cô. Những ngón tay nó quấn quanh cổ chân An.

Bà ngoại từng nói phải trả lại giọng nói đầu tiên. An bật máy lần nữa, đặt nó sát khe tường.

Giọng mẹ vang lên. Bóng người trong phòng khẽ ngẩng đầu. Lần đầu tiên, An nghe thấy một tiếng thở dài thật sự, không phải tiếng nhại lại.

“Con gái của mẹ,” giọng ấy nói. “Đi đi.”

Bức tường đổ sập.',
'published'
WHERE @story_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM chapters WHERE story_id=@story_id AND chapter_number=8);

INSERT INTO chapters (story_id, chapter_number, title, content, status)
SELECT @story_id, 9, 'Người được bước ra',
'Bụi vữa phủ kín hành lang. An bị hất vào phòng khách, tai ù đặc. Khi cô mở mắt, trời bên ngoài đã gần sáng. Căn nhà im lặng đến đáng sợ.

Bức tường phía đông sụp xuống, để lộ căn phòng cũ. Chiếc giường nhỏ vẫn ở đó. Trên bàn có một chiếc lược gỗ, một quyển sách thiếu nhi và tấm ảnh mẹ bế An. Nhưng không có ai.

An bò đến gần. Trên nền nhà, cô tìm thấy chiếc nhẫn bạc mẹ từng đeo. Nó nằm cạnh một vệt bụi hình bàn tay.

Đứa trẻ biến mất. Cuộn băng cassette đã cháy đen, chỉ còn cuộn nhựa méo mó. An nhặt chiếc nhẫn lên, cảm thấy một hơi ấm rất nhẹ.

Dì Hạnh cùng vài người hàng xóm chạy sang khi nghe tiếng đổ. Họ đưa An ra ngoài, gọi người kiểm tra căn nhà. Không ai tìm thấy thi thể hay dấu vết của một người thứ hai. Chỉ có những bức tường rỗng, bên trong nhét đầy tóc, mảnh vải và những chiếc răng sữa đã ngả màu.

Dì Hạnh ôm An, khóc nức nở. “Bà con đã giữ được nó lâu lắm rồi.”

An hỏi mẹ có từng được tự do không. Dì Hạnh không trả lời. Dì chỉ nói bà ngoại đã dành cả đời để giữ cho An không bị căn nhà gọi trở lại.

Căn nhà được niêm phong. An rời làng ngay hôm ấy, mang theo chiếc nhẫn bạc và cuốn sổ bìa nâu. Trên xe, cô mở trang cuối cùng. Một dòng chữ mới xuất hiện dưới nét mực cũ:

“Con đã làm đúng. Đừng quay lại.”

An nhìn ra cửa kính. Cánh đồng lùi dần phía sau. Cô tưởng mọi chuyện đã kết thúc.

Nhưng khi xe đi qua cây cầu đầu làng, điện thoại cô rung lên. Một tin nhắn thoại được gửi từ số của mẹ. Không có số điện thoại, không có tên người gửi, chỉ hiện một biểu tượng âm thanh.

An không bấm nghe.

Cô tắt máy, cất điện thoại vào túi. Một lúc sau, người ngồi ghế bên cạnh quay sang hỏi:

“Cô có nghe ai gọi không?”

An nhìn người ấy. Đó là một bà cụ xa lạ, đội nón lá, tay ôm chiếc giỏ tre.

“Không,” An đáp.

Bà cụ gật đầu, rồi nhìn xuống chiếc nhẫn trên tay An.

“Vậy tốt. Vì có những căn nhà không cần tường để giữ người đâu.”',
'published'
WHERE @story_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM chapters WHERE story_id=@story_id AND chapter_number=9);

INSERT INTO chapters (story_id, chapter_number, title, content, status)
SELECT @story_id, 10, 'Tiếng gọi trong chuyến xe',
'An chuyển đến một thành phố khác, thuê căn hộ nhỏ ở tầng cao và bắt đầu lại cuộc sống. Cô không còn nghe tiếng gõ trong tường. Những giấc mơ về mẹ cũng thưa dần. Chiếc nhẫn bạc được cô cất trong hộp, cuốn sổ của bà ngoại nằm dưới đáy tủ.

Ba tháng trôi qua. Một buổi tối, An đi làm về muộn. Thang máy chung cư dừng ở tầng mười hai. Khi cửa mở, hành lang trống không. Cô bước ra, nghe tiếng chuông báo thang máy vang lên phía sau.

Một giọng phụ nữ gọi: “An ơi.”

Cô đứng sững.

Giọng ấy rất giống mẹ, nhưng không còn dịu dàng. Nó khàn và xa, như vọng qua một đường ống dài.

An không quay lại. Cô đi thẳng đến cửa căn hộ, mở khóa và bước vào. Khi cánh cửa đóng lại, tiếng gọi cũng dừng.

Cô tự nhủ đó chỉ là ký ức. Cả ngày hôm sau, An cố quên chuyện ấy. Nhưng tối đến, khi đang rửa bát, cô nghe ba tiếng gõ từ phía tủ bếp.

Cộc. Cộc. Cộc.

Cô tắt vòi nước. Một giọng trẻ con vang lên sau cánh tủ:

“Chị An, em lạnh.”

An lùi lại. Cô không trả lời. Cô lấy điện thoại gọi dì Hạnh, nhưng cuộc gọi không kết nối. Màn hình tự mở ứng dụng ghi âm. Một tệp mới xuất hiện, đặt tên bằng ngày hôm đó.

An bấm phát vì tưởng đó là lỗi máy.

Đầu tiên là tiếng xe khách, tiếng gió và tiếng người trò chuyện. Sau đó là giọng bà cụ trên chuyến xe hôm trước:

“Có những căn nhà không cần tường để giữ người đâu.”

Tiếp theo là giọng mẹ cô, rất nhỏ:

“An, con đừng sợ. Mẹ không gọi con về.”

An nín thở.

“Mẹ chỉ muốn con biết, thứ đã ra khỏi căn nhà không phải tất cả.”

Một tiếng cười trẻ con chen vào. Tệp ghi âm kết thúc bằng ba tiếng gõ.

An mở hộp, lấy chiếc nhẫn bạc. Trên mặt nhẫn xuất hiện một vết nứt nhỏ. Bên trong hộp, cuốn sổ bìa nâu tự mở ra ở trang cuối. Dưới dòng chữ “Đừng quay lại”, có thêm một câu viết bằng nét chữ của chính An:

“Nếu nó gọi bằng giọng của con, đừng tin.”

Cùng lúc đó, điện thoại sáng lên. Màn hình khóa hiện cuộc gọi đến từ chính số của An.

Cô nhìn vào màn hình. Chuông reo lần thứ nhất. Rồi lần thứ hai.

Đến lần thứ ba, một giọng nói vang lên từ loa, rõ ràng và bình tĩnh:

“An ơi, mở cửa. Chị ở ngoài này.”

An ngẩng đầu nhìn cánh cửa căn hộ.

Từ phía bên kia, có tiếng chìa khóa tra vào ổ.

Và cô hiểu ra điều bà ngoại đã cố viết suốt những năm cuối đời: căn nhà không giữ người bằng tường. Nó giữ người bằng giọng nói. Chỉ cần một người trả lời, nó sẽ biết đường đi theo.

An tắt nguồn điện thoại, lùi khỏi cửa và ngồi xuống sàn. Cô không đáp lại, dù giọng nói ngoài kia tiếp tục gọi tên cô suốt đêm.

Sáng hôm sau, hàng xóm thấy cửa căn hộ mở hé. Bên trong không có dấu hiệu xô xát. Trên bàn chỉ còn chiếc nhẫn bạc, cuốn sổ bìa nâu và một tờ giấy.

Tờ giấy ghi: “Nếu có ai hỏi An đi đâu, hãy nói cô ấy chưa từng sống ở đây.”

Căn hộ được cho thuê lại một tuần sau đó. Người thuê mới là một cặp vợ chồng trẻ. Đêm đầu tiên, họ nghe tiếng chuông điện thoại reo trong tủ bếp.

Người chồng mở tủ.

Bên trong không có điện thoại, chỉ có một giọng phụ nữ thì thầm từ vách gỗ:

“Đừng trả lời nếu nghe ai gọi tên mình.”

Rồi một giọng khác, giống hệt giọng người chồng, cất lên từ phía sau lưng:

“Em ơi, anh về rồi.”',
'published'
WHERE @story_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM chapters WHERE story_id=@story_id AND chapter_number=10);

COMMIT;

-- Kiem tra ket qua sau khi import:
SELECT s.id, s.title, s.story_kind, s.status, COUNT(c.id) AS total_chapters
FROM stories s
LEFT JOIN chapters c ON c.story_id=s.id
WHERE s.slug='can-nha-giu-tieng-nguoi'
GROUP BY s.id, s.title, s.story_kind, s.status;
