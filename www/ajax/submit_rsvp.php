<?php
    session_start();
    // immediately exit if session party not established
    if (!isset($_SESSION['party_id'])) {
        die('0');
    }
    $party_id = $_SESSION['party_id'];
    
    // get db connection
    require_once(__DIR__."/../../include/mysql.php");
    $rsvp_conn->begin_transaction();
    // get guests
    $stmt = $rsvp_conn->prepare("SELECT id FROM guests WHERE party_id = ?");
    $stmt->bind_param('i', $party_id);
    $stmt->bind_result($guest_id);
    $stmt->execute();
    $guest_ids = array();
    while ($stmt->fetch()) {
        if ($rsvp_conn->error) {
            $rsvp_conn->rollback();
            die('0');
        }
        $guest_ids[] = $guest_id;
    }
    // have guest ids, update each one
    foreach ($guest_ids as $guest_id) {
        if (isset($_POST['guest'.$guest_id])) {
            // coming!
            $meal_id = $_POST['guest'.$guest_id.'_meal'];
            // get name is plus-one
            if (isset($_POST['name_guest'.$guest_id])) {
                // is a plus-one
                $name = htmlspecialchars($_POST['name_guest'.$guest_id]); // be careful with user input....
                $stmt = $rsvp_conn->prepare("UPDATE guests SET response = 1, name = ?, meal_id = ? WHERE id = ?");
                $stmt->bind_param('sii', $name, $meal_id, $guest_id);
                $stmt->execute();
            } else {
                // is a regular guest
                $stmt = $rsvp_conn->prepare("UPDATE guests SET response = 1, meal_id = ? WHERE id = ?");
                $stmt->bind_param('ii', $meal_id, $guest_id);
                $stmt->execute();
            }
        } else {
            // not coming...
            if (isset($_POST['name_guest'.$guest_id])) {
                // plus one
                $name = htmlspecialchars($_POST['name_guest'.$guest_id]); // be careful with user input....
                $stmt = $rsvp_conn->prepare("UPDATE guests SET response = 0, name = ? WHERE id = ?");
                $stmt->bind_param('si', $name, $guest_id);
                $stmt->execute();
            } else {
                // regular guest
                $stmt = $rsvp_conn->prepare("UPDATE guests SET response = 0 WHERE id = ?");
                $stmt->bind_param('i', $guest_id);
                $stmt->execute();
            }
        }
        // failure -- exit
        if ($rsvp_conn->error) {
            $rsvp_conn->rollback();
            die('0');
        }
    }
    // all set, everything successful
    $rsvp_conn->commit();
    $_SESSION['responded'] = true;
    die('1');
?>
