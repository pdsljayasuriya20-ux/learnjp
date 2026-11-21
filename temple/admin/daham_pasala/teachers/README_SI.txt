දහම් පාසල් ගුරු මොඩියුලය - ස්ථාපනය

මෙම folder එකේ index.php, create.php, view.php, edit.php, delete.php, print.php සහ common.php අඩංගුය.

1. මුලින් ඔබේ දැනට ඇති temple/admin/daham_pasala/teachers folder එක backup කරන්න.
2. මෙම teachers folder එක project එකේ temple/admin/daham_pasala/teachers/ තුළට copy/replace කරන්න.
3. phpMyAdmin > temple_db තෝරා migration.sql ගොනුවේ ALTER TABLE විධාන දෙක ධාවනය කරන්න. දැනට ඇති දත්ත මකා නොදමයි.
4. teachers/uploads/ folder එක PHP/web server user හට ලිවිය හැකි බව තහවුරු කරන්න. PHP මගින් folder එක ස්වයංක්‍රීයව සෑදීමට උත්සාහ කරයි.
5. ගුරු ලැයිස්තුවේ “සියලු ගුරුවරු PDF” හෝ පුද්ගලික view එකේ PDF භාවිත කළ විට print window එක විවෘත වේ. Browser print dialog එකෙන් Destination: Save as PDF තෝරා save කරන්න. මෙය external PDF library එකක් අවශ්‍ය නොවන ක්‍රමයයි.

විශේෂාංග:
- අධ්‍යාපන සුදුසුකම් checkbox කිහිපයක් තෝරාගැනීම
- සේවා ආරම්භක දිනයෙන් වර්ෂය, මාසය සහ සේවා කාලය ස්වයංක්‍රීයව පෙන්වීම
- JPG/PNG/WEBP profile photo (උපරිම 4MB)
- ගුරුවරයෙකුගේ වාර්තාව සහ සියලු ගුරුවරුන්ගේ print/PDF වාර්තාව
- තොරතුරු edit/delete කිරීම

දැනට පවතින Auth::requirePermission('view_dayakayan') permission එක භාවිතා කරයි.
