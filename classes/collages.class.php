<?php
class Collages {
    public static function create_personal_collage() {
        $CollageCount = G::$DB->scalar("
            SELECT
                count(*)
            FROM collages
            WHERE CategoryID = '0'
                AND Deleted = '0'
                AND UserID = ?
            ", G::$LoggedUser['ID']
        );

        if ($CollageCount >= G::$LoggedUser['Permissions']['MaxCollages']) {
            $CollageID = G::$DB->scalar("
                SELECT min(ID)
                FROM collages
                WHERE CategoryID = '0'
                    AND Deleted = '0'
                    AND UserID = ?
                ", G::$LoggedUser['ID']
            );
            header("Location: collage.php?id=$CollageID");
            exit;
        }
        $NameStr = G::$LoggedUser['Username'] . "'s personal collage" . ($CollageCount > 0 ? ' no. ' . ($CollageCount + 1) : '');
        $Description = 'Personal collage for ' . G::$LoggedUser['Username'] . '. The first 5 albums will appear on his or her [url=' . SITE_URL . '/user.php?id= ' . G::$LoggedUser['ID'] . ']profile[/url].';
        G::$DB->prepared_query("
            INSERT INTO collages
                   (Name, Description, UserID, CategoryID)
            VALUES (?,    ?,           ?,      0)
            ", $NameStr, $Description, G::$LoggedUser['ID']
        );
        $CollageID = G::$DB->inserted_id();
        header('Location: collage.php?id='.$CollageID);
        exit;
    }

    public static function collage_cover_row(array $Group) {
        $GroupID = $Group['ID'];
        $GroupYear = $Group['Year'];
        $Artists = $Group['Artists'];
        $ExtendedArtists = $Group['ExtendedArtists'];
        $TorrentTags = new Tags($Group['TagList']);
        $WikiImage = $Group['WikiImage'];

        $DisplayName = '';
        if (!empty($ExtendedArtists[1]) || !empty($ExtendedArtists[4]) || !empty($ExtendedArtists[5])|| !empty($ExtendedArtists[6])) {
            unset($ExtendedArtists[2]);
            unset($ExtendedArtists[3]);
            $DisplayName = Artists::display_artists($ExtendedArtists, false);
        } elseif (count($Artists) > 0) {
            $DisplayName = Artists::display_artists(['1' => $Artists], false);
        }
        $DisplayName .= $Group['Name'];
        if ($GroupYear > 0) {
            $DisplayName = "$DisplayName [$GroupYear]";
        }
        $Tags = display_str($TorrentTags->format());
        $PlainTags = implode(', ', $TorrentTags->get_tags());
        ob_start();
?>
        <li class="image_group_<?=$GroupID?>">
            <a href="torrents.php?id=<?=$GroupID?>" class="bookmark_<?=$GroupID?>">
<?php    if ($WikiImage) { ?>
                <img class="tooltip_interactive" src="<?=ImageTools::process($WikiImage, true)?>" alt="<?=$DisplayName?>" title="<?=$DisplayName?> <br /> <?=$Tags?>" data-title-plain="<?="$DisplayName ($PlainTags)"?>" width="118" />
<?php    } else { ?>
                <div style="width: 107px; padding: 5px;"><?=$DisplayName?></div>
<?php    } ?>
            </a>
        </li>
<?php
        return ob_get_clean();
    }

    public static function bbcodeUrl($id, $url = null) {
        $cacheKey = 'bbcode_collage_' . $id;
        if (($name = G::$Cache->get_value($cacheKey)) === false) {
            $name = G::$DB->scalar('SELECT Name FROM collages WHERE id = ?', $id);
            G::$Cache->cache_value($cacheKey, $name, 86400 + rand(1, 3600));
        }
        return $name
            ? $url
                ? sprintf('<a href="%s">%s</a>', $url, $name)
                : sprintf('<a href="collages.php?id=%d">%s</a>', $id, $name)
            : "[collage]{$id}[/collage]";
    }
}
