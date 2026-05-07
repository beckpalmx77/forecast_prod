<!-- TopBar -->
<nav class="navbar navbar-expand navbar-light bg-navbar topbar mb-4 static-top">
    <button id="sidebarToggleTop" class="btn btn-link rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>
    <div class="text-white" id="clock"></div>
    <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown no-arrow mx-1">
            <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-toggle="dropdown"
               aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-heart"></i><span>&nbsp;10 ปี สงวนออโต้คาร์</span>
            </a>
        </li>

        <!--li class="nav-item dropdown no-arrow mx-1">
            <a class="nav-link dropdown-toggle" href="manage-message.php" target="_self">
                <span class="badge badge-danger">Message</span>&nbsp;<i class="fa fa-bell"></i>
            </a>
        </li-->

        <li class="nav-item dropdown no-arrow">
            <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in"
                 aria-labelledby="searchDropdown">
                <form class="navbar-search">
                    <div class="input-group">
                        <input type="text" class="form-control bg-light border-1 small"
                               placeholder="What do you want to look for?"
                               aria-label="Search" aria-describedby="basic-addon2" style="border-color: #710714;">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="button">
                                <i class="fas fa-search fa-sm"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </li>

        <div class="topbar-divider d-none d-sm-block"></div>
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown"
               aria-haspopup="true" aria-expanded="false">
                <?php $src = $_SESSION['sex']==="M" ? "img/boy.png" : "img/boy-cp.png"; ?>
                <img class="img-profile rounded-circle" src="<?php echo $src;?>" style="max-width: 60px">

                <span class="ml-2 d-none d-lg-inline text-white small"><!--?php echo $_SESSION['first_name'] . " " . $_SESSION['last_name']; ?--></span>
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                <!--a class="dropdown-item" href="#">
                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                    Profile
                </a>
                <a class="dropdown-item" href="#">
                    <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                    Settings
                </a-->
                <!--a class="dropdown-item" href="#">
                    <i class="fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i>
                    Activity Log
                </a-->
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="javascript:void(0);" data-toggle="modal" data-target="#logoutModal">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                    Logout
                </a>
            </div>
        </li>
    </ul>
</nav>


<script>
    $(document).ready(function(){

        function load_unseen_notification(view = '')
        {
            $.ajax({
                url:"fetch.php",
                method:"POST",
                data:{view:view},
                dataType:"json",
                success:function(data)
                {
                    //alert(data);
                    $('.dropdown-menu').html(data.notification);
                    if(data.unseen_notification > 0)
                    {
                        $('.count').html(data.unseen_notification);
                    }
                }
            });
        }

        load_unseen_notification();

        $(document).on('click', '.dropdown-toggle', function(){
            $('.count').html('');
            load_unseen_notification('yes');
        });

        //setInterval(function(){
            //load_unseen_notification();;
        //}, 5000);

    });
</script>
<!-- Topbar -->

<script>
    function updateClock() {
        const options = {
            timeZone: 'Asia/Bangkok',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            weekday: 'long',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        };
        const bangkokTime = new Intl.DateTimeFormat('th-TH', options).format(new Date());
        document.getElementById('clock').textContent = bangkokTime;
    }

    // Update the clock every second
    setInterval(updateClock, 1000);

    // Initial call to display the clock immediately
    updateClock();
</script>
