-- --------------------------------------------------------
--
-- Структура таблицы `#__plg_joomshopping_two_lang`
--

DROP TABLE IF EXISTS `#__plg_joomshopping_two_lang`;
CREATE TABLE IF NOT EXISTS `#__plg_joomshopping_two_lang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `word` varchar(255) NOT NULL,
  `transcription` varchar(255) NOT NULL COMMENT 'текст в обратной раскладке',
  `redirect` varchar(512) NOT NULL COMMENT 'Link to the product',
  `date_added` datetime NOT NULL COMMENT 'Date Added',
  `hits` int(11) NOT NULL DEFAULT '0',
  `manually` tinyint(1) NOT NULL DEFAULT '0',
  `dont_use` tinyint(1) NOT NULL DEFAULT '0',
  `systems` tinyint(11) NOT NULL DEFAULT '0' COMMENT 'System Commands',
  PRIMARY KEY (`id`),
  UNIQUE KEY `word` (`word`),
  UNIQUE KEY `transcription` (`transcription`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
-- Дамп данных таблицы `#__plg_joomshopping_two_lang`
--

INSERT INTO `#__plg_joomshopping_two_lang` (`id`, `word`, `transcription`, `redirect`, `date_added`, `hits`, `manually`, `dont_use`, `systems`) VALUES
(7, '*create dictionary', '*create dictionary', '', '0000-00-00 00:00:00', 3117, 0, 0, 1),
(8, '*clear dictionary', '*clear dictionary', '', '0000-00-00 00:00:00', 14, 0, 0, 1);

