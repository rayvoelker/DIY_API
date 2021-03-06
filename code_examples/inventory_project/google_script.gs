/*
// 
// Ray Voelker
// University of Dayton Libraries
// 300 College Park Dayton, OH 45419-1360
// rvoelker1@udayton.edu
// ray.voelker@gmail.com
//  If you have any questions or comments 
//  about this script, page or feature, please
//  feel free to contact me.
//
*/

/* 
This is a Google Apps Script intended to be used in conjunction with a RESTful API (item_barcode.php)
To use this script:
1. Create a new Google Spreadsheet, select 
	"Tools -> Script Editor" from the menu
2. Copy and paste this code into the script editor, save it, and then select 
	"Resources -> Current project's triggers"
3. Add a trigger for the onEdit() function ...
	Run: onEdit
	Events: From spreadsheet :On edit
	select "Save" and give proper authorization
4. Test the sheet ... pasting this barcode into the first cell should yield results: R701498024
*/
function onEdit(e) {
  var sheet = SpreadsheetApp.getActiveSheet();
  // cell must have a value, be only one row, and be from the first sheet
  if( e.range.getValue() && (e.range.getNumRows() == 1) && sheet.getIndex() == 1) {
    //Logger.log( e.range.getValue() );
    var value = e.range.getValue(),
        spread_sheet_name = SpreadsheetApp.getActiveSpreadsheet().getName();
    e.range.setValue(value.toLowerCase());

    var url = 'http://library2.udayton.edu/api/getInventoryData/item_barcode.php?'
      + 'barcode=' + value;
    var result = UrlFetchApp.fetch(url);  
    var json_data = JSON.parse(result.getContentText());
    
    //make sure we have data back ...
    if(json_data) {
      if (json_data.call_number_norm) {
        e.range.offset(0,1).setValue('=\"' + json_data.call_number_norm.toUpperCase() + '\"');
      }
      //escape all double quotes
      if (json_data.best_title) {
        e.range.offset(0,2).setValue('=\"' + json_data.best_title.replace(/"/g, '""') + '\"');
      }
      e.range.offset(0,3).setValue('=\"' + json_data.location_code + '\"');
      e.range.offset(0,4).setValue('=\"' + json_data.item_status_code + '\"');
    }
    
    if(json_data.due_gmt != null) {
      e.range.offset(0,5).setValue('=\"' + json_data.due_gmt.substring(0, json_data.due_gmt.length - 3) + '\"');
    }
    else {
      e.range.offset(0,5).setValue('=\"\"');
    }
    
    e.range.offset(0,6).setValue('=\"' + Utilities.formatDate(new Date(), "GMT-4:00", "yyyy-MM-dd' 'HH:mm:ss") + '\"');
    e.range.offset(0,7).setValue(e.range.getRow());
    e.range.offset(0,8).setValue(spread_sheet_name);
    
  } //end if
} //end function onEdit()