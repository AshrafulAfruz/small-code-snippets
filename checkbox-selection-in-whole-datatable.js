
//jquery , js , datatable
var dtable = $('#datatable1').DataTable({
            language: {
                searchPlaceholder: "Search data"
            },
            paging: true
        })


$(document).on('change','#checkAll',function(){
       if($(this).is(':checked'))
       {
         var cells = dtable.column(0).nodes() // Cells from 1st column
             for (var i = 0; i < cells.length; i += 1) {
                 cells[i].querySelector("input[type='checkbox']").checked = true;
             }
       }
       else
       {
         var cells = dtable.column(0).nodes() // Cells from 1st column
             for (var i = 0; i < cells.length; i += 1) {
                 cells[i].querySelector("input[type='checkbox']").checked = false;
             }
       }
    })

   /**************** 
    *if  .dataTable() constructor was used then, line var cells = dtable.column(0).nodes()  would be like, var cells = dtable.api().column(0).nodes() 
   ************/
