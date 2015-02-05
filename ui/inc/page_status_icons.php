    <!-- First Row -->
    <div class="row">
        <!-- Simple Stats Widgets -->
        <div class="col-sm-6 col-lg-3">
            <a href="javascript:void(0)" class="widget">
                <div class="widget-content widget-content-mini themed-background-success text-light-op">
                    <i class="fa fa-clock-o"></i> <strong>Contacts</strong>
                </div>
                <div class="widget-content text-right clearfix">
                    <div class="widget-icon pull-left">
                        <i class="gi gi-user text-muted"></i>
                    </div>
                    <h2 class="widget-heading h3 text-success">
                        <i class="fa"></i> <strong><span data-toggle="counter" data-to="<?php echo $userStats->get(Access2Me\Data\UserStats::CONTACTS_COUNT); ?>"></span></strong>
                    </h2>
                    <span class="text-muted">AUTHENTICATED</span>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3">
            <a href="javascript:void(0)" class="widget">
                <div class="widget-content widget-content-mini themed-background-warning text-light-op">
                    <i class="fa fa-clock-o"></i> <strong>Invites</strong>
                </div>
                <div class="widget-content text-right clearfix">
                    <div class="widget-icon pull-left">
                        <i class="gi gi-wifi text-muted"></i>
                    </div>
                    <h2 class="widget-heading h3 text-warning">
                        <i class="fa"></i> <strong><span data-toggle="counter" data-to="<?php echo $userStats->get(Access2Me\Data\UserStats::INVITES_COUNT); ?>"></span></strong>
                    </h2>
                    <span class="text-muted">ACCEPTED</span>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3">
            <a href="<?php echo htmlentities($appConfig['siteUrl'] . '/ui/filters.php'); ?>" class="widget">
                <div class="widget-content widget-content-mini themed-background-danger text-light-op">
                    <i class="fa fa-clock-o"></i> <strong>Filtering</strong>
                </div>
                <div class="widget-content text-right clearfix">
                    <div class="widget-icon pull-left">
                        <i class="gi gi-wallet text-muted"></i>
                    </div>
                    <h2 class="widget-heading h3 text-danger">
                        <i class="fa"></i> <strong><span data-toggle="counter" data-to="<?php echo $userStats->get(Access2Me\Data\UserStats::FILTERS_COUNT); ?>"></span></strong>
                    </h2>
                    <span class="text-muted">ACTIVE FILTERS</span>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3">
            <a href="javascript:void(0)" class="widget">
                <div class="widget-content widget-content-mini themed-background text-light-op">
                    <i class="fa fa-clock-o"></i> <strong>Activity</strong>
                </div>
                <div class="widget-content text-right clearfix">
                    <div class="widget-icon pull-left">
                        <i class="gi gi-cardio text-muted"></i>
                    </div>
                    <h2 class="widget-heading h3">
                        <strong><span data-toggle="counter" data-to="<?php echo $userStats->get(Access2Me\Data\UserStats::MESSAGES_COUNT); ?>"></span></strong>
                    </h2>
                    <span class="text-muted">TOTAL MESSAGES</span>
                </div>
            </a>
        </div>
        <!-- END Simple Stats Widgets -->
    </div>
    <!-- END First Row -->
