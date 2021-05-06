<?php


namespace mod_mooduell;

/**
 * Class game_finished contains functions to execute when the event game_finished is triggered
 * @package mod_mooduell
 */
class game_finished
{
    /**
     * @var null mooduellid
     */
    var $mooduellid = null;

    public static function update_highscores_table(){ //TODO: pass $mooduellid
        // TODO implement highscores table update logic
        echo "in function: update_highscores_table - event triggered successfully!";
    }
}