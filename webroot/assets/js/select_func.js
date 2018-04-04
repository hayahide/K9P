/**
 * Created by MY_PC on 10/4/2016.
 */
$(function () {

    // ADD LIST WORKERS
    $('body').on('click',".staff-add-btn-on",

        function (e) {

            var sefl = $(this);
            var div_bg = document.getElementsByClassName("list-add-btn");
            $(div_bg).css("background", "none");
            var div_parent = sefl.parents(".list-add-btn").first();

            $(div_parent).css("background","#dcf0fb");

            var div_bg01 = document.getElementsByClassName("register-btn01");
            $(div_bg01).css("background", "#f3f3f3");
            $(div_bg01).css("color","#000");

            $(sefl).css("background","#d08989");
            $(sefl).css("color","#FFF");

            document.getElementById('add-worker').style.width= '70%';
            document.getElementById('left-worker').style.width ='62%';
            document.getElementById('left-worker').style.float = 'left';
            document.getElementById('right-car').style.display ='none';
            document.getElementById('right-level').style.display ='none';
            document.getElementById('right-worker').style.display ='block';
            document.getElementById('right-worker').style.float = 'right';
            document.getElementById('right-worker').style.width ='38%';
            // document.getElementById('right-worker').style.borderLeft = '2px solid #f3f3f3';
			return false;

        }
    );

    //ADD LIST CARS

    $('body').on('click',".car-add-btn-on",

        function (e) {

            var sefl = $(this);
            var div_bg = document.getElementsByClassName("list-add-btn");
                $(div_bg).css("background", "none");
            var div_parent = sefl.parents(".list-add-btn").first();

                $(div_parent).css("background","#dcf0fb");

            var div_bg01 = document.getElementsByClassName("register-btn01");
            $(div_bg01).css("background", "#f3f3f3");
            $(div_bg01).css("color","#000");

            $(sefl).css("background","#d08989");
            $(sefl).css("color","#FFF");

            document.getElementById('add-worker').style.width= '70%';
            document.getElementById('left-worker').style.width ='62%';
            document.getElementById('left-worker').style.float = 'left';
            document.getElementById('right-worker').style.display ='none';
            document.getElementById('right-level').style.display ='none';
            document.getElementById('right-car').style.display ='block';
            document.getElementById('right-car').style.float = 'right';
            document.getElementById('right-car').style.width ='38%';
            document.getElementById('right-car').style.borderLeft = '2px solid #f3f3f3';
			return false;

        }
    );


    //ADD LIST LEVELS

    $('body').on('click',".level-add-btn-on",

        function (e) {

            var sefl = $(this);
            var div_bg = document.getElementsByClassName("list-add-btn");
            $(div_bg).css("background", "none");
            var div_parent = sefl.parents(".list-add-btn").first();

            $(div_parent).css("background","#dcf0fb");

            var div_bg01 = document.getElementsByClassName("register-btn01");
            $(div_bg01).css("background", "#f3f3f3");
            $(div_bg01).css("color","#000");

            $(sefl).css("background","#d08989");
            $(sefl).css("color","#FFF");

            document.getElementById('add-worker').style.width= '70%';
            document.getElementById('left-worker').style.width ='62%';
            document.getElementById('left-worker').style.float = 'left';
            document.getElementById('right-worker').style.display ='none';
            document.getElementById('right-car').style.display ='none';
            document.getElementById('right-level').style.display ='block';
            document.getElementById('right-level').style.float = 'right';
            document.getElementById('right-level').style.width ='38%';
            // document.getElementById('right-car').style.borderLeft = '2px solid #f3f3f3';
            return false;

        }
    );

        //yen add When li.list-add class on click

	/*
        $('body').on('click',"li.date-add",

            function (e) {

                $('.date-add').removeClass('active');

                var $this = $(this);
                if (!$this.hasClass('active')) {
                    $this.addClass('active');
                }

                $('#schedule-site-info-container').css("width", "64%");
                $('#schedule-date-left').css("width", "24%")
                $('#schedule-worker-right').css("display","block");
                $('#worker-close').css("display","none");
                    return false;
            }
        );
	*/


});
