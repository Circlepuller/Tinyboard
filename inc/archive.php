<?php

/*
 *  Copyright (c) 2010-2019 Tinyboard Development Group
 */

/**
 * Archive a thread
 * 
 * @param int $id The thread ID to archive
 */
function archiveThread($id)
{
    global $config, $board;

    $query = prepare(sprintf('SELECT `thread` FROM ``posts_%s`` WHERE `id` = :id', $board['uri']));
    $query->bindValue(':id', $id, PDO::PARAM_INT);
    $query->execute() or error(db_error($query));
    $thread = $query->fetchColumn();

    if ($thread !== null)
        error($config['error']['nonexistant']);

    $query = prepare(sprintf('UPDATE ``posts_%s`` SET `archived` = true WHERE `id` = :id', $board['uri']));
    $query->bindValue(':id', $id, PDO::PARAM_INT);
    $query->execute() or error(db_error($query));

    buildThread($id);
    purgeArchive();
    buildArchiveIndex();
}

/**
 * Purge the thread archive.
 * 
 * @param string $lifetime The maximum amount of days to keep a thread in the archive
 * @param int $threads The maximum amount of threads to keep in the archive at once
 */
function purgeArchive($lifetime, $threads)
{
    global $board;

    if ($threads) {
        $query = prepare(sprintf('SELECT `id` FROM ``posts_%s`` WHERE `thread` IS NULL AND `archived` = true AND `featured` = false ORDER BY `bump` DESC LIMIT :offset, 9001', $board['uri']));
        $query->bindValue(':offset', $threads, PDO::PARAM_INT);
        $query->execute() or error(db_error($query));

        while ($id = $query->fetchColumn())
            deletePost($id, false, false);

        if ($query->rowCount() > 0)
            modLog("Automatically deleted {$query->rowCount()} archived threads due to thread archive limit");
    }

    if ($days) {
        $query = prepare(sprintf('SELECT `id` FROM ``posts_%s`` WHERE `thread` IS NULL AND `archived` = true AND `featured` = false AND `bump` < :lifetime'));
        $query->bindValue(':lifetime', strtotime('-' . $lifetime), PDO::PARAM_INT);
        $query->execute() or error(db_error($query));

        while ($id = $query->fetchColumn())
            deletePost($id, false, false);

        if ($query->rowCount() > 0)
            modLog("Automatically deleted {$query->rowCount()} archived threads due to expiration date");
    }
}

/**
 * Feature an archived thread.
 * 
 * @param int $id The ID of the archived thread to feature
 */
function featureThread($id)
{
    global $config, $board;
    
    buildThread($id);
    buildFeaturedIndex();
}

/**
 * Delete a featured thread from the archive.
 * 
 * @param int $id The ID of the featured archived thread to delete
 */
function deleteFeatured($id)
{
    global $config, $board;

    buildFeaturedIndex();
}

function rebuildArchiveIndexes()
{
    global $config;

    if ($config['archive']['max_lifetime'] || $config['archive']['max_threads'])
        purgeArchive($config['archive']['max_lifetime'], $config['archive']['max_threads']);

    buildArchiveIndex();

    if ($config['archive']['featured'])
        buildFeaturedIndex();
}

function buildArchiveIndex()
{
    global $config, $board;
}

function buildFeaturedIndex()
{
}