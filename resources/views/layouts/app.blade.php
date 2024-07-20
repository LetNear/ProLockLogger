<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="{{ asset('home/assets/img/favicons/favicon.png') }}" rel="icon">
    <link rel="stylesheet" href="{{ asset('/css/timeinoutcomlab.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="{{ asset('/timeinandout/timedate.js') }}"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script> --}}
    <title>Computer Laboratory</title>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">

</head>
<body style="background-color: #75daff">
<div class="row align-items-start">
     <div class="">
            <h1 class="text-center">Attendance Monitoring System</h1>
            <h5 style="font-style: Helvetica; text-align: center; font-size: 1.5em; font-weight: bold;">Please tap your ID</h5>
     </div>
  </div>

    <section class="card-section">

      <div class="container">
        <div class="row align-items-start">
          <div class="col-md-9">
            <div class="card w-120 id-card" style="background-color: rgb(36, 69, 141)">
                <div class="card-body" >
                    <div id="msg"></div>
                    <br>
                    <div id="msgg"></div>
                    <div class="row align-items-start">
                        <div class="col-md-4">
                            <div class="photo-frame">
                            <img id="view_images" src="/img/default.png" alt="User Photo" class="user-photo">
                        </div>
                      </div>
                      <div class="col-md-4">
                            <div class="user-details">
                                <p><strong>NAME:</strong> <input type="text" id="name" class="input-text1" disabled autocomplete="off"></p>
                                <br>
                                <p><strong>STUDENT NUMBER:</strong> <input type="text" id="studno" class="input-text1" disabled autocomplete="off"></p>
                                <br>
                                <p><strong>ASSIGNED COMPUTER:</strong> <input type="text" id="asscomputer" class="input-text1" disabled autocomplete="off"></p>
                                <p><strong></strong> <input type="text" id="rfidcard" class="input-text" style="text-align: center; opacity: 0;" minlength="10" maxlength="10" autocomplete="off"></p>                                
                            </div>
                          </div>
                          <div class="col-md-4" style="text-align: center;">
                            <h5 style="font-weight: bold; color: #000000; text-decoration: underline; font-size: .7em;">
                                Recently Log
                            </h5>
                            <table class="table table-striped table-sm">
                                <thead>
                                  <tr style="color: #000000; font-size: .8em">
                                    <th scope="col">Name</th>
                                    <th scope="col">Seat No.</th>
                                    <th scope="col">Time In</th>
                                  </tr>
                                </thead>
                                {{-- <tbody>
                                    @foreach($gettop10 as $row)
                                  <tr>
                                    <td style="text-transform: capitalize;">{{$row->name}}</td>
                                    <td>{{$row->seat_number}}</td>
                                    <td>{{$row->time_in}}</td>
                                  </tr>
                                  @endforeach
                                </tbody> --}}
                              </table>
                              {{-- <div class="d-flex">
                                {!! $gettop10->links() !!}
                            </div> --}}
                        </div>
                      </div>
                </div>

                <div class="container time-boxes mb-4">
                    <div class="row align-items-start">
                      <div class="col">
                        <label style="font-size: 1em;">Time-In:</label>
                        <input type="text" id="timeInstudent" class="input-text digital-clock" disabled style="width: 130px; height: 30px; text-align: center;" class="digital-clock">
                      </div>
                      <div class="col">
                        <label style="font-size: 1em;">Time-Out:</label>
                        <input type="text" id="timeOutstudent" class="input-text digital-clock" disabled style="width: 130px; height: 30px; text-align: center;" class="digital-clock">
                      </div>
                    </div>
                    <br>
                </div>


              </div>
          </div>
          <div class="col-md-0"></div>
        </div>
        <div class="clockdate">
            <p id="clock"></p>
            <p>|</p>
            <p id="date"></p>
        </div>
      </div>
    </section>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
</body>
</html>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>
$(document).ready(function(){

$('#rfidcard').focus();
$('body').mousemove(function(e){
    e.preventDefault();
    $('#rfidcard').focus();
});

    $('#rfidcard').on('change', function(){
        if($(this).val().length >= 10){
            let data = {
                rfid_number : $("#rfidcard").val(),
            };

            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: "{{ route('comlabcreateattendance') }}",
                type: "POST",
                data: data,
                timeout: 2000,
                cache: false,
                dataType: 'json',
                success: function(response) {
                    if (response.status == "timein") {
                        $('#view_images').attr("src", '/storage/student_photos/' + response.photo);
                        $('#datestudent').val(response.date_student);
                        $('#timeInstudent').val(response.timein_student);
                        $('#namestudent').val(response.name);
                        $('#lrnstudent').val(response.lrn);
                        $('#msg').html('<div class="alert alert-success" style="color: green; font-weight: bold; font-size: 18px;"><i class="fa fa-check"></i> Time In Successfully! Seat Number: ' + response.seat_number + '</div>');
                        setTimeout(function() {
                                    window.location.reload();
                                }, 5000);
                        } else if (response.status == "timeout") {
                            $('#view_images').attr("src", '/storage/student_photos/' + response.photo);
                            $('#datestudent').val(response.date_student);
                            $('#timeInstudent').val(response.timein_student);
                            $('#timeOutstudent').val(response.timeout_student);
                            $('#namestudent').val(response.name);
                            $('#lrnstudent').val(response.lrn);
                            $('#msg').html('<div class="alert alert-danger" style="color: red; font-weight: bold; font-size: 18px;"><i class="fa fa-check"></i> Time Out Successfully!</div>');
                        } else if (response.status == "capacity_exceeded") {
                            $('#msg').html('<div class="alert alert-warning" style="background-color: yellow; font-size: 25px; color: red; font-weight: bold;"><i class="bi-exclamation-triangle-fill"></i><strong class="mx-2"><b>Warning! The Capacity Exceeded!</b></div>');
                        } else if (response.status == "no_available_seats") {
                            $('#msg').html('<div class="alert alert-warning" style="background-color: yellow; font-size: 25px; color: red; font-weight: bold;"><i class="bi-exclamation-triangle-fill"></i><strong class="mx-2"><b>Warning! No Available Seats!</b></div>');
                        } else if (response.status == "nolibschedule") {
                             $('#msg').html('<div class="alert alert-warning alert-dismissible d-flex align-items-center fade show" style="background-color: yellow; font-size: 25px; color: red; font-weight: bold;"> <i class="bi-exclamation-triangle-fill"></i><strong class="mx-2"><b>Warning! You don`t have a schedule at comlab right now!</b></div>');
                            }
                            else if (response.status == "nothing") {
                             $('#msg').html('<div class="alert alert-warning alert-dismissible d-flex align-items-center fade show" style="background-color: yellow; font-size: 25px; color: red; font-weight: bold;"> <i class="bi-exclamation-triangle-fill"></i><strong class="mx-2"><b>Warning! RFID Not Register!</b></div>');
                            }else if(response.status == "today"){
                                    $('#msg').html('<div class="alert alert-warning alert-dismissible d-flex align-items-center fade show" style="background-color: yellow; font-size: 25px; color: red; font-weight: bold;"> <i class="bi-exclamation-triangle-fill"></i><strong class="mx-2"><b>You`re done today, time in and time out again tomorrow</b></div>');

                                }
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    },
                    error: function(response) {
                        console.log("Failed");
                    }
                });
        }
    });
});


</script>

<script type="text/javascript">

    document.addEventListener('contextmenu', (e) => e.preventDefault());

    function ctrlShiftKey(e, keyCode) {
      return e.ctrlKey && e.shiftKey && e.keyCode === keyCode.charCodeAt(0);
    }

    document.onkeydown = (e) => {
      if (
        event.keyCode === 123 ||
        ctrlShiftKey(e, 'I') ||
        ctrlShiftKey(e, 'J') ||
        ctrlShiftKey(e, 'C') ||
        (e.ctrlKey && e.keyCode === 'U'.charCodeAt(0))
      )
        return false;
    };
  </script>
  <script>
      $('body').keydown(function(e) {
        if(e.which==123){
            e.preventDefault();
        }
        if(e.ctrlKey && e.shiftKey && e.which == 73){
            e.preventDefault();
        }
        if(e.ctrlKey && e.shiftKey && e.which == 75){
            e.preventDefault();
        }
        if(e.ctrlKey && e.shiftKey && e.which == 67){
            e.preventDefault();
        }
        if(e.ctrlKey && e.shiftKey && e.which == 74){
            e.preventDefault();
        }
    });
!function() {
        function detectDevTool(allow) {
            if(isNaN(+allow)) allow = 100;
            var start = +new Date();
            debugger;
            var end = +new Date();
            if(isNaN(start) || isNaN(end) || end - start > allow) {
                console.log('DEVTOOLS detected '+allow);
            }
        }
        if(window.attachEvent) {
            if (document.readyState === "complete" || document.readyState === "interactive") {
                detectDevTool();
              window.attachEvent('onresize', detectDevTool);
              window.attachEvent('onmousemove', detectDevTool);
              window.attachEvent('onfocus', detectDevTool);
              window.attachEvent('onblur', detectDevTool);
            } else {
                setTimeout(argument.callee, 0);
            }
        } else {
            window.addEventListener('load', detectDevTool);
            window.addEventListener('resize', detectDevTool);
            window.addEventListener('mousemove', detectDevTool);
            window.addEventListener('focus', detectDevTool);
            window.addEventListener('blur', detectDevTool);
        }
    }();
  </script>
