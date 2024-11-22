<?php

namespace app\model;

use support\Model;

class Server extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'yt_monitor_server';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
}