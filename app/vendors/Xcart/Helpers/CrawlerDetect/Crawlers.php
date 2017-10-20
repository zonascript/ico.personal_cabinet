<?php

namespace Xcart\Helpers\CrawlerDetect;

use Jaybizzle\CrawlerDetect\Fixtures\AbstractProvider;

class Crawlers extends AbstractProvider
{
    protected $data = null;
    private $raw_data
        = ["X-Cart info"                     => ["X-Cart info", 'curl', 'PycURL'],
           "Other"                           => ["ShortLinkTranslate", "YahooCacheSystem", "ContextAd", "Xenu", "PHP/5.4.32", "Disqus"],
           "Bing"                            => ["bing"],
           "TheFind"                         => ["com.thefind.Shopping/3.2.1"],
           "Altavista"                       => ["Scooter", "vscooter", "Mercator", "AltaVista-Intranet"],
           "Northern Light"                  => ["Gulliver"],
           "Inktomi"                         => ["Yahoo!"],
           "Teoma"                           => ["teoma_agent1"],
           "Amazon"                          => ["Amazon CloudFront", "Amazon"],
           "Atomz"                           => ["Atomz"],
           "Buscaplus"                       => ["Buscaplus Robi"],
           "ShopperTom"                      => ["ShopperTom", "Pimonster"],
           "Baidu"                           => ["Sogou"],
           "CanSeek"                         => ["CanSeek"],
           "DeepIndex"                       => ["DeepIndex"],
           "Ditto"                           => ["DittoSpyder"],
           "Domanova"                        => ["Jack"],
           "Euroseek"                        => ["Arachnoidea"],
           "EZResults"                       => ["EZResult"],
           "Fireball"                        => ["KIT-Fireball"],
           "Fyber search"                    => ["FyberSearch"],
           "Goo"                             => ["moget/2.0"],
           "Girafa"                          => ["Aranha"],
           "Hoppa"                           => ["Toutatis"],
           "Hubat"                           => ["Hubater"],
           "IlTrovatore"                     => ["IlTrovatore-Setaccio"],
           "IncyWincy"                       => ["IncyWincy"],
           "InTags"                          => ["Mole2"],
           "Look"                            => ["NetResearchServer"],
           "Look smart"                      => ["MantraAgent"],
           "Loop improvements"               => ["NetResearchServer"],
           "mozDex"                          => ["mozDex"],
           "Northern light"                  => ["Gulliver"],
           "Objects Search"                  => ["ObjectsSearch"],
           "Pico Search"                     => ["PicoSearch"],
           "OpenFind"                        => ["Openfind piranha"],
           "Scrub the Web"                   => ["Scrubby"],
           "SingingFish"                     => ["asterias"],
           "Kototoi"                         => ["Kototoi"],
           "Teradex Mapper"                  => ["Teradex_Mapper"],
           "Vivante"                         => ["Vivante Link Checker"],
           "Walhello"                        => ["appie"],
           "Websmostlinked.com"              => ["Nazilla"],
           "WebTop"                          => ["MuscatFerret"],
           "WiseNut"                         => ["ZyBorg"],
           "Tooter"                          => ["Tooter"],
           "Alligator"                       => ["Alligator"],
           "BatchFTP"                        => ["BatchFTP"],
           "ChinaClaw"                       => ["ChinaClaw"],
           "Download accelerator"            => ["DA"],
           "NetZIP"                          => ["Download Demon", "NetZip Downloader", "SmartDownload"],
           "Download Master"                 => ["Download Master"],
           "Download Ninja"                  => ["Download Ninja"],
           "Download Wonder"                 => ["Download Wonder"],
           "Ez Auto Downloader"              => ["Ez Auto Downloader"],
           "FreshDownload"                   => ["FreshDownload"],
           "Go!Zilla"                        => ["Go!Zilla"],
           "GetRight"                        => ["GetRight"],
           "GetSmart"                        => ["GetSmart"],
           "HiDownload"                      => ["HiDownload"],
           "FlagGet"                         => ["JetCar", "FlashGet"],
           "Kapere"                          => ["Kapere"],
           "Kontiki"                         => ["Kontiki Client"],
           "LeechFTP"                        => ["LeechFTP"],
           "LeechGet"                        => ["LeechGet"],
           "LightningDownload"               => ["LightningDownload"],
           "Mass Downloader"                 => ["Mass Downloader"],
           "MetaProducts"                    => ["MetaProducts"],
           "NetAnts"                         => ["NetAnts"],
           "NetButler"                       => ["NetButler"],
           "NetPumper"                       => ["NetPumper"],
           "Net Vampire"                     => ["Net Vampire"],
           "Nitro Downloader"                => ["Nitro Downloader"],
           "Octopus"                         => ["Octopus"],
           "PuxaRapido"                      => ["PuxaRapido"],
           "RealDownload"                    => ["RealDownload"],
           "SpeedDownload"                   => ["SpeedDownload"],
           "WebDownloader"                   => ["WebDownloader"],
           "WebLeacher"                      => ["WebLeacher"],
           "WebPictures"                     => ["WebPictures"],
           "X-Uploader"                      => ["X-Uploader"],
           "DigOut4U"                        => ["DigOut4U"],
           "DISCoFinder"                     => ["DISCoFinder"],
           "eCatch"                          => ["eCatch"],
           "EirGrabber"                      => ["EirGrabber"],
           "ExtractorPro"                    => ["ExtractorPro"],
           "FairAd"                          => ["FairAd Client"],
           "iSiloWeb"                        => ["iSiloWeb"],
           "MS IE 4.0"                       => ["MSProxy"],
           "NexTools"                        => ["NexTools"],
           "Offline Explorer"                => ["Offline Explorer"],
           "NetAttache"                      => ["NetAttache"],
           "PageDown"                        => ["PageDown"],
           "ParaSite"                        => ["ParaSite"],
           "SiteMapper"                      => ["SiteMapper"],
           "SiteSnagger"                     => ["SiteSnagger"],
           "Teleport Pro"                    => ["Teleport Pro"],
           "Web2Map"                         => ["Web2Map"],
           "WebAuto"                         => ["WebAuto"],
           "WebCopier"                       => ["WebCopier"],
           "Webdup"                          => ["Webdup"],
           "WebFetch"                        => ["WebFetch"],
           "WebReaper"                       => ["WebReaper"],
           "Website eXtractor"               => ["Website eXtractor"],
           "WebSnatcher"                     => ["WebSnatcher"],
           "WebStripper"                     => ["WebStripper"],
           "WebTwin"                         => ["WebTwin"],
           "WebVCR"                          => ["WebVCR"],
           "WebZIP"                          => ["WebZIP"],
           "World Wide Web Offline Explorer" => ["WWWOFFLE"],
           "Qwantify"                        => ["Qwantify"],
           "Hyperic"                         => ["Hyperic"],
           "DigitEyes"                       => ["DigitEyes"],
        ];

    private function clearData($data)
    {
        return preg_replace('/([\+\/\\\.@\(\)])/i', '\\\${1}', $data);
    }

    public function __construct()
    {
        $data = [];
        foreach ($this->raw_data as $k => $v) {
            $v      = $this->clearData(implode('|', $v));
            $data[] = "($v)";
        }
        $this->data = $data;
    }

    public function getCrawlerName($n)
    {
        $h = array_keys($this->raw_data);

        return $h[$n];
    }

}