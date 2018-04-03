-- phpMyAdmin SQL Dump
-- version 4.7.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 10, 2018 at 10:16 AM
-- Server version: 5.7.20-0ubuntu0.16.04.1
-- PHP Version: 7.0.26-2+ubuntu16.04.1+deb.sury.org+2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tpa`
--

-- --------------------------------------------------------

--
-- Table structure for table `appcrontracker`
--

CREATE TABLE `appcrontracker` (
  `id` int(11) NOT NULL,
  `accounting_system` varchar(255) NOT NULL,
  `item_type` varchar(255) NOT NULL,
  `user` varchar(255) NOT NULL,
  `cron_time` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `appcrontracker`
--

INSERT INTO `appcrontracker` (`id`, `accounting_system`, `item_type`, `user`, `cron_time`) VALUES
(1, 'Quickbook', 'Supplier', 'FRN100000384', '2017-12-20T04:10:33-07:00'),
(2, 'Quickbook', 'Customer', 'FRN100000384', '2017-12-20T04:10:36-07:00'),
(3, 'quickbook', 'ACCREC', 'FRN100000384', '2017-12-20T04:10:41-07:00'),
(4, 'xero', 'invoice', 'FRN100000386', '2017-12-20T12:13:12'),
(5, 'Quickbook', 'Supplier', 'FRN100000384', '2018-01-02T22:32:19-07:00'),
(6, 'Quickbook', 'Customer', 'FRN100000384', '2018-01-02T22:32:26-07:00'),
(7, 'quickbook', 'ACCREC', 'FRN100000384', '2018-01-02T22:32:29-07:00'),
(8, 'quickbook', 'ACCPAY', 'FRN100000384', '2018-01-02T22:34:38-07:00');

-- --------------------------------------------------------

--
-- Table structure for table `appDetail`
--

CREATE TABLE `appDetail` (
  `id` int(11) NOT NULL,
  `clientId` varchar(100) CHARACTER SET utf8 DEFAULT NULL,
  `clientSecret` varchar(150) CHARACTER SET utf8 DEFAULT NULL,
  `code` text,
  `signingSecret` varchar(150) CHARACTER SET utf8 DEFAULT NULL,
  `callbackUrl` varchar(600) CHARACTER SET utf8 DEFAULT NULL,
  `scope` varchar(100) CHARACTER SET utf8 DEFAULT NULL,
  `apimSubscription` varchar(150) NOT NULL,
  `accounting_system` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `appDetail`
--

INSERT INTO `appDetail` (`id`, `clientId`, `clientSecret`, `code`, `signingSecret`, `callbackUrl`, `scope`, `apimSubscription`, `accounting_system`) VALUES
(1, 'eb144eda9046cc0e46c6', '61341b705205055b4abeda11ba5e74842728d3e7', 'c7438ac5593a9fb799d74cddc084f2fbd6c1b8410c3f2a5cf746f9863bd0338e', 'd1dddec2fc769abe7b91b7c019a3e6665da009df', 'http://localhost/cisageone/sageoneData', 'full_access', '9b2394b00cf141e0ade928b140cc67bf', 'sageone'),
(2, 'c96790d42d843c1c9d65014485ad4f634d8eda4c667d66fefa10baf364d4de1e', '0fddf2c3be3d07eb0c7b429d6f64e65848a140f7eb5f870ff0e953faa717009e', '2a601ec987c27757001e1bee7d4140f25f5a65953db31f2c096f433fd7b6cac1', '317dee813a28faabccdc9a4790a9ccfe4c84b0446f329143e2c05b56f4788a5d', 'https://blazeaccounting.co.uk/cisageone/freshbookData', NULL, '', 'freshbook'),
(3, 'f8ee2448-a552-47ba-b939-061ab748d538', 'vXuvckmpEhgj', 'nAyJ!IAAAAIknakTmTw-m61FD2uy4Fvr9tdii-jS4jTKvApYXK0AP8QAAAAF-qdjrQTFUkQZLVQVFGa4RAlPRyCJ3XE59yt5RFatAO4wf-eJkeiMEg-lr8v4Ipx1A4oR5eglSWledz5575wVqUfxMb2lmDRdbp5yRbFq6ihCBa5ewXGgPA66VcWt4oFw3fygGWU6hV0O98skiJapmvonnF_SHejduPFyayMPIBhEt6KyQXf5maEmpwBIY-mFPbWwuLEUgRavh7Ny3-jEubbAXK_PKLk3XQby-wvYKY0y5TxCcgQA1Z12JVL_UUDq-TcddAzqOuduDXc-iAo4Bui5n2EeIWOajGF6hWT_VSua9SyY153QQgmcZnAK9-cY', NULL, 'https://development.frenns.com/importdata/exactDataNL', NULL, '', 'exactNL'),
(4, 'd418013b-aca6-48eb-9d21-3ed115374ea4', 'sdimMPPsj6QX', NULL, NULL, 'http://localhost/tpa/exactDataUK', NULL, '', 'exactUK'),
(5, '2yW_dZPcpT6jKYtuRuW62A', 'cHepT64B-AoGsmiR_oP9nA', NULL, NULL, 'http://localhost/tpa/freeagentData', NULL, '', 'freeagent');

-- --------------------------------------------------------

--
-- Table structure for table `synccredential`
--

CREATE TABLE `synccredential` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `usernumber` varchar(255) NOT NULL,
  `UserName` varchar(255) DEFAULT NULL,
  `Password` varchar(255) DEFAULT NULL,
  `realm` varchar(255) NOT NULL,
  `consumer_key` varchar(255) DEFAULT NULL,
  `consumer_secret` varchar(255) DEFAULT NULL,
  `access_token` text NOT NULL,
  `access_token_secret` varchar(255) DEFAULT NULL,
  `refresh_token` text NOT NULL,
  `accountId` varchar(255) NOT NULL,
  `accounting_system` varchar(255) NOT NULL,
  `code` text,
  `apiKey` varchar(255) DEFAULT NULL,
  `expires_in` int(11) DEFAULT NULL,
  `country` varchar(255) NOT NULL,
  `updated_at` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `synccredential`
--

INSERT INTO `synccredential` (`id`, `customer_id`, `usernumber`, `UserName`, `Password`, `realm`, `consumer_key`, `consumer_secret`, `access_token`, `access_token_secret`, `refresh_token`, `accountId`, `accounting_system`, `code`, `apiKey`, `expires_in`, `country`, `updated_at`) VALUES
(2, 377, 'FRN100000377', '', NULL, '', NULL, '', 'c5fc81fff60f878fb37f24df0e321986bef70844', '', 'a5787ea4b369f9381f45d66bf6d7a1530a414319', '', 'sageone', NULL, NULL, NULL, 'GB', '2017-06-15 00:00:00'),
(3, 378, 'FRN100000378', '', NULL, '', NULL, '', '0c29a007760a068251f7ea1bf3f244fc653a4650', '', 'af6d10b13c6de1085128e7fd4827f4721561b83d', '', 'sageone--', NULL, NULL, NULL, 'GB', '2017-06-15 00:00:00'),
(7, 259, 'FRN100000380', '', NULL, '', NULL, '', 'f6a2cf9eb3c62de101af97c425a94a5d9c4d8e8ebb55c15ed7bc3b742ebe8d1f', '', '5adbce5191437b42d93771f2eec97c1321d72eaa2a62f02a98c359abcede41b0', 'O8dlj', 'freshbook--', '2a601ec987c27757001e1bee7d4140f25f5a65953db31f2c096f433fd7b6cac1', NULL, NULL, 'GB', '0000-00-00 00:00:00'),
(8, 381, 'FRN100000381', 'sskhalsa315@gmail.com', 'admin@786', '', NULL, NULL, '', NULL, '', '', 'kashflow--', NULL, NULL, NULL, '', '0000-00-00 00:00:00'),
(9, 382, 'FRN100000382', 'sarvjeet315@yopmail.com', 'admin@786', '', NULL, NULL, '', NULL, '', '', 'kashflow--', NULL, NULL, NULL, '', '0000-00-00 00:00:00'),
(10, 383, 'FRN100000383', NULL, NULL, '193514577570534', 'qyprdFOTOaG6p1GpHWBTy2eKJL6afK', 'xfEi7cSsPSmfj50UnT9Nx7XGPasXrQ3ygwRBpePi', 'lvprdA8vSYDHYi6c4YeMXDgWj687kBwbDyEArU0g7wVXO8si', 'kQ2gCRl5RisyP7eNP41RBGpf8r9nOp8mZosnWWKF', '', '', 'quickbook--', NULL, NULL, NULL, '', '2017-07-06 00:00:00'),
(11, 384, 'FRN100000384', NULL, NULL, '193514536790519', 'qyprdFOTOaG6p1GpHWBTy2eKJL6afK', 'xfEi7cSsPSmfj50UnT9Nx7XGPasXrQ3ygwRBpePi', 'qyprdBtpNyuTR1fprzV3raTTa02fD55mvFzPf9K1mVBeIA4I', 'Ylhp2rUpLOvfZ0VVahv7zguExutjlLlD35xODxxC', '', '', 'quickbook', NULL, NULL, NULL, '', '2017-07-06 00:00:00'),
(12, 385, 'FRN100000385', NULL, NULL, '', NULL, NULL, 'd23107d9fa501ec512139974abec64930c46bdcb7cc2243cd28b57e7b82c8c12', NULL, '89f3b2726fbaa8fa6a8fa777685176941d5faa6117f4952f19ee534fc3ef2b5f', 'Az5le', 'freshbook', '863b3e70297806fe45deb90306a40bb1f70b0d48ee9391a5509d410b945bf368', NULL, NULL, '', '0000-00-00 00:00:00'),
(13, 386, 'FRN100000386', NULL, NULL, '', 'BA3NT65GMN14YZBBGWMDWNH4DGEXUW', 'ZAMYY20ERXDRCF3UXOEG4Q4K3CFTTO', '', NULL, '', '', 'xero', NULL, NULL, NULL, '', '0000-00-00 00:00:00'),
(14, 387, 'FRN100000387', 'business5@frenns.com', NULL, '', NULL, NULL, '', NULL, '', '', 'clearbooks--', NULL, '3061d4e15002900434741e4826eeff00', NULL, '', '0000-00-00 00:00:00'),
(15, 388, 'FRN100000388', 'digitaltest35@gmail.com', 'admin#123', '', NULL, NULL, '', NULL, '', '', 'kashflow', NULL, NULL, NULL, '', '0000-00-00 00:00:00'),
(16, 389, 'FRN100000389', NULL, NULL, '', NULL, NULL, '', NULL, '', '', 'clearbooks--', NULL, '55f619615005368829a27ba34dee45ba', NULL, '', '0000-00-00 00:00:00'),
(17, 390, 'FRN100000390', NULL, NULL, '', NULL, NULL, 'gAAAADMV3Dcbi5OkO4zQdudd0dICKsT3RRFQre8A3U68ucEQIv6TjMJMCrZ_gV0vc6HRJFzOUvF8pAdxgIod5bkXXuCL3xBiCpAfKzJ3f6z95y9v_uk9532k4AdV1thw7xmlcjzLwYLbY7u79iiPygZg57IM-O2yvrpoUD27-XxxazacFAEAAIAAAAAx1DHgbFYY9QQaXSOWy2CIFIbhGXU3zTjkCusk2lbt_X2CKUv-TuXfIgUcmaKyTyNZ8a2h7kSZWOQLB-2J9f0BhIuX0XB53_GAxO3XeRv6NpGqhFs4jWkfRnkunRR5Dy4uZy5s-24HSvQxATeXeXRpXTzu333N5qKJgnKt_qa3McSnV0qAFO88Z9jhJG6U09-A7uyU1n5537-i5w89FhdszZlleuoQOJ3UWwP0o8QMC72ERyfv5gp6BDzgRBg0JphRbDTeBXiaFlOV-3l4Wb9AicH94wqqhHHbHwzJVOKmVyRmR-hwVV__D7-SDdtT0ZbwF1zWzdqKA13oK32mLlFf34Jc2v4oZKhT_obBJYwUZA', NULL, '9PZu!IAAAAOAzUynMvelGQvwiP1EOTt-2kMqm1ZTveqyYNDoojyc7sQAAAAFMrHc0SSTP0axds-wu0mJrrYJFDi1vf1QjVqIZ89gIHpOhUOdGq-Ph18saiwg4LDWb6M1xqbM13QEgdLEtiuDwv0lcz44CR8zSxV3xXNh9Fn4cldpCgduVh8OIMgZodMLagc3mWfpzqgT7LujnDTi1mYqBUp7CuhyWUtQoMmt_cZ0w7SkHQviLX7TlFKb7de8xNJcHVDGrp6r_V6GzvPQJFDsn4dwxUpjkB5IATKEFeg', '', 'exactNL', 'nAyJ!IAAAAIknakTmTw-m61FD2uy4Fvr9tdii-jS4jTKvApYXK0AP8QAAAAF-qdjrQTFUkQZLVQVFGa4RAlPRyCJ3XE59yt5RFatAO4wf-eJkeiMEg-lr8v4Ipx1A4oR5eglSWledz5575wVqUfxMb2lmDRdbp5yRbFq6ihCBa5ewXGgPA66VcWt4oFw3fygGWU6hV0O98skiJapmvonnF_SHejduPFyayMPIBhEt6KyQXf5maEmpwBIY-mFPbWwuLEUgRavh7Ny3-jEubbAXK_PKLk3XQby-wvYKY0y5TxCcgQA1Z12JVL_UUDq-TcddAzqOuduDXc-iAo4Bui5n2EeIWOajGF6hWT_VSua9SyY153QQgmcZnAK9-cY', NULL, 1502865976, 'nl', '0000-00-00 00:00:00'),
(18, 391, 'FRN100000391', NULL, NULL, '', NULL, NULL, 'gAAAAD4ut2cQUoApjr3YC3Xle0qJp_6AA2wdYckoD9B1GFkLdwHTxYwD3j3NgLPljNBqSXrePbMHOYSnMGWQJC0AGNlBJVFWVKhZH4op1DMqzHZHcaB0OObVj6GwXIqZiamENNrdx8b5FvKjlzYF6oKVK07o58wuL1w02nsSQ2mnRem-JAEAAIAAAADB8XKJWSsXwLlR3UiMSbOcxIl1ZAAF1sJOOAM-VzW6LcCQWImYg-mfZ04q3J5jNxGmaDLKiWhupYVKMjETDOvJxuRtBo9SVqC4Bif5aqCZYPa3_H6E6ZC1KDq1m5nQXKcWKWW7DnwpFCn4bateKY_GBfUbS4T-M85HY3f9PvtdL1hjHeHqb0W05eebRY3DtkHUysi5icyOvZ3n5_3BoanOqXO5BBGVIRoXzQxSRmCj-2vA5Woh_vCx8HXaozhtyTZBwSOvrkS7RdEGsX2-c51tthKXHzeAcABklvjWkr9TeSI-CoWCb8pt_bWPhOEtcNzewfgckwbUKjaHYNUNcVMFf96QpyHZZ__7cTgMd91Y84E6ichnjG8-9BfF_0N1c34', NULL, '9PZu!IAAAALx7ef7U1woO2Jg5F71uOfV8N5iyvl5ah8xHkQmMATWSsQAAAAF0JYRXrq4KaCl_be7Al7YuNU6t2-1QX1N2t6i3YjiajzIOWzsZYI7JdNmWEYoISmjcjDhgHLLArocQ2WixDRck6b-nsKApHrHWgOljYmjNY62HbRqeHwhsS_LEcqSSM1iVKZ7yCmK33N2wP5tknZpaLC-kPLKt4KznynRzPZzdgmDTeJ3logTJtWcHbwOOC2gxXA3SiJ7kuyPlTtby3J0mWquSWqMOBvzjsqxlE2JDdg', '', 'exactNL---', 'nAyJ!IAAAAI89vGqQPcnM6bpvkOaVcGp7DTpsoqh633GxEK0CYjmj8QAAAAHanzdYMCrjL-0Obj8wbeqVV7YMjVWIMrtx3vWFmsIqOubmAo_4H_r5AKzIrbW1G3PvZrDaxXZJdSyIoYwVIYaKg5CqbQsRePGXwmuV9NwAiWmcQ6-XbwsA4NhLEbvxk44L9sgB0aXejrAczSwBBxGRnmlMxhQEpvivqXheuW54ec1Ovt4FC5bpPIB6xw9MrePGmHEyfQFILOnQvDmLrKL8p60d5ulX-sL6FvxExGlq_G1BAHVMtoVqXNy2yJjWaAnOxjcBDyGhoFnxnsnI4ifHip3q9XusoN3ceZ1otiFzGO1zdtjqIMxz0ftvuK7YG9E', NULL, 1501581468, 'nl', '0000-00-00 00:00:00'),
(19, 392, 'FRN100000392', NULL, NULL, '', NULL, NULL, 'gAAAAGk5XqxPlXLo8rSioV81x5Rjrp2BBk5-uWWUbQet0mnFtjflTHoCl7gcriT4RORb3jeVbXpzCi563ZmyysWKq-cl-GaEcQCu11KzaT-nivlpld_ZnvHxGwkD3Mamtmtagz280OnB7KvgrA2eC5541SLcpLvL40ieknkYUQLBeW2YJAEAAIAAAAA6RCzD7EtmsHm67oULnPFB8mvTGo_OR61O9DSP-MosptHTjH7W8RnOyGhtv13pZrXpnygXnvJLmqnbv36nd6C5efwfClzx77I2a5rL3GGU7bmupNJwX3jcP6wwGfLiOZ4DQgnZlmMjZ-W6vxdhj83igOdW64N5_t7kyzlqwuSUm6ZzXyTbJSeNZss6IdhxlEJeeJseG7GNFwCVhBlcsNwZp9k9Bg4-uvcCmR02TTMVkQfprqlHmGstHQp-kBi_WwYx50D88xYpft9Hqn_YIc32OVGSIw_OXgoOBqRYLb50DVqU0d1yHo_fFG1ndcWDfwc3qKT8nrr6zIl9DGgkqzEWByAy53lvWABW5ETGk-z6PsVR4rLPv6ydLw5Snuyy078', NULL, '8LB1!IAAAAMb7PUGz5uxe7J5F_bqi7bYt5OthyN77AZxFiyxkyNkdsQAAAAGnR4zN3xwrQRpGvv7LXusMrwVW6pUp3XzTKb5tRcnjJweYqjQ8b2c05EezZHV1nZhp7S-pbSvqyzubZRQNghaQdE3XhFFPFLu4JZ7XO7O7LuAiMM3eprOfrnvSNDgEk74AlZueJeQI_NJP3gq7F8Xh7fYwHT71KwK2nWb4Al394nzCiMcbLhKPgHm5Y2ewVAlAn5OlpjHiKr_9ifbXhpMHIvygXBZoNrgvcYObXwuWZw', '', 'exactUK', 'wLCS!IAAAALyUKGnKb73xF8GcgtqPzr35SI6GCTIU2w3NjeMqLXvA8QAAAAFOEJJb0ZvnEF8EvY-j4fc9dIqh4KCF189ZEqDiZ3xer_7vWxdcWXgX0x7IBD87MuWjt6hgx7oSWYo8FX8ys0efzwjIPWAcwXjzy1K0zBIw-M2LBD97kHtE3Gr410VbFLNQO14I6I8HsfIb3owUOUaWaC3lwBaX4B9dEKnNTpIUTjIBp6ZQocNffxVojVpRIPnu41ypDUJn2p9r7W045GIYSJN9w1AwvXJjYWUfQ77EQyJ8A5reDVvGK2vg-TvVAWoIaUUhtWQgqiiYYCn5YWu9VdZGh72pVk55uW_-cSLEgy0N2KnILY4GpHXohFgd8iU', NULL, 1501651163, 'uk', '0000-00-00 00:00:00'),
(20, 393, 'FRN100000393', 'sarvjeet@1wayit.com', 'admin@786', '', NULL, NULL, '', NULL, '', '', 'reeleezee', NULL, NULL, NULL, '', '0000-00-00 00:00:00'),
(25, 394, 'FRN100000394', 'kapil@1wayit.com', 'Fa@12345', '', NULL, NULL, '1eVGqnl5HcJCReKI5cfrBQT1ziYsc50twiS60u1wh', NULL, '12YJik0bsVWV1xtI9hmDpAUy8b2S_NkTPUPM2R5SU', '', 'freeagent--', NULL, NULL, NULL, '', '2017-08-19 00:00:00'),
(26, 395, 'FRN100000395', 'muki@yopmail.com', 'Fa@12345', '', NULL, '', '1IUJe94qALqMG4Ip2fIXd1EEy1o3YuEzb14nkeTQS', NULL, '1Pt-WjPz1y_Jbphw4w210AaEdpItIKHMg7un7L7Yu', '', 'freeagent--', NULL, NULL, NULL, '', '2017-08-19 00:00:00'),
(27, 396, 'FRN100000396', 'digitaltest35@gmail.com', 'admin#123', '', NULL, NULL, '1IMpnSsryEiIunIHK8-6BXuFpqfaMMn7g2I8Ev3Bt', NULL, '1PaPaaX-BtpITbe1QGkMmwy_Snzl05O_TwGj-R_On', '', 'freeagent', NULL, NULL, NULL, '', '2017-08-25 00:00:00'),
(28, 400, 'FRN100000400', 'digitaltest35@gmail.com', 'admin#123', '', NULL, NULL, '', NULL, '', '', 'clearbooks', NULL, '8686c051514270163acb3460492f88b3', NULL, '', '0000-00-00 00:00:00'),
(29, 401, 'FRN100000401', 'business500@frenns.com', 'Frenns2017!', '', NULL, NULL, '', NULL, '', '', 'clearbooks--', NULL, '919a3611510060683ba44d5b5377cad7', NULL, '', '0000-00-00 00:00:00'),
(30, 402, 'FRN100000402', 'API000611', 'Twinfield@12345', 'TWF-SAAS', NULL, NULL, '', NULL, '', '', 'twinfield', NULL, NULL, NULL, '', '');

-- --------------------------------------------------------

--
-- Table structure for table `syncinvoice`
--

CREATE TABLE `syncinvoice` (
  `syncinvoice_id` int(11) UNSIGNED NOT NULL,
  `frenns_id` varchar(255) NOT NULL,
  `unique_frenns_id` varchar(200) DEFAULT NULL,
  `company_account_number` varchar(255) DEFAULT NULL,
  `collection_date` date DEFAULT NULL,
  `creation_date` varchar(200) DEFAULT NULL,
  `last_updated` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `address` varchar(255) NOT NULL DEFAULT '',
  `postcode` varchar(255) NOT NULL DEFAULT '',
  `city` varchar(255) NOT NULL DEFAULT '',
  `country` varchar(255) NOT NULL DEFAULT '',
  `company_number` varchar(255) DEFAULT '',
  `vat_registration_number` varchar(255) DEFAULT '',
  `contact_person` varchar(255) NOT NULL DEFAULT '',
  `phone_no` varchar(255) DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `type` varchar(255) NOT NULL DEFAULT '',
  `invoice_number` varchar(255) DEFAULT '',
  `issue_date` varchar(255) DEFAULT NULL,
  `due_date` varchar(200) DEFAULT NULL,
  `payment_terms` varchar(255) DEFAULT '',
  `payment_method` varchar(255) DEFAULT '',
  `delivery_date` varchar(100) DEFAULT NULL,
  `currency` varchar(255) NOT NULL DEFAULT '',
  `amount` float(11,2) DEFAULT '0.00',
  `vat_amount` float(11,2) DEFAULT '0.00',
  `outstanding_amount` float(11,2) DEFAULT '0.00',
  `paid` varchar(255) NOT NULL DEFAULT '',
  `pay_date` varchar(100) DEFAULT NULL,
  `invoiceId` varchar(255) DEFAULT NULL,
  `customerId` varchar(255) DEFAULT NULL,
  `updateId` varchar(200) NOT NULL,
  `update_at` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `syncinvoice`
--

INSERT INTO `syncinvoice` (`syncinvoice_id`, `frenns_id`, `unique_frenns_id`, `company_account_number`, `collection_date`, `creation_date`, `last_updated`, `account_number`, `name`, `address`, `postcode`, `city`, `country`, `company_number`, `vat_registration_number`, `contact_person`, `phone_no`, `email`, `type`, `invoice_number`, `issue_date`, `due_date`, `payment_terms`, `payment_method`, `delivery_date`, `currency`, `amount`, `vat_amount`, `outstanding_amount`, `paid`, `pay_date`, `invoiceId`, `customerId`, `updateId`, `update_at`) VALUES
(1, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-12-14T23:18:50-08:00', '2017-12-14T23:18:55-08:00', '', 'Carol Emert', '1229 winter hawk drive', '', '', '', '', '', 'Carol Emert', '', '', 'ACCREC', '3033', '2017-12-14T23:18:50-08:00', '2018-01-13', '', '', '', 'USD', 1977.16, 0.00, 1977.16, 'false', '', '183', 'quickbook-FRN100000384-80', 'quickbook-FRN100000384-S183', NULL),
(2, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-12-14T23:18:36-08:00', '2017-12-14T23:18:42-08:00', '', 'Vincent Giordano', '37 White street', '32080', 'st augustine', '', '', '', 'Vincent Giordano', '', '', 'ACCREC', '3034', '2017-12-14T23:18:36-08:00', '2018-01-13', '', '', '', 'USD', 1830.70, 0.00, 1830.70, 'false', '', '182', 'quickbook-FRN100000384-79', 'quickbook-FRN100000384-S182', NULL),
(3, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-12-14T23:18:22-08:00', '2017-12-14T23:18:28-08:00', '', 'Don and Claire Sabatini', '4967Coquina Crossing', '', '', '', '', '', 'Don and Claire Sabatini', '', '', 'ACCREC', '3072', '2017-12-14T23:18:22-08:00', '2018-01-13', '', '', '', 'USD', 1169.56, 0.00, 1169.56, 'false', '', '181', 'quickbook-FRN100000384-78', 'quickbook-FRN100000384-S181', NULL),
(4, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-12-14T23:18:06-08:00', '2017-12-14T23:18:14-08:00', '', 'Maria Cavanaugh', '121 Senora Ct', '', '', '', '', '', 'Maria Cavanaugh', '', '', 'ACCREC', '3100', '2017-12-14T23:18:06-08:00', '2018-01-13', '', '', '', 'USD', 811.94, 0.00, 811.94, 'false', '', '180', 'quickbook-FRN100000384-77', 'quickbook-FRN100000384-S180', NULL),
(5, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-12-14T23:17:50-08:00', '2017-12-14T23:17:57-08:00', '', 'Janet Monsalvage', '417 Ports mouth Bay Ave', '', 'Nocatee', '', '', '', 'Janet Monsalvage', '', '', 'ACCREC', '3105', '2017-12-14T23:17:50-08:00', '2018-01-13', '', '', '', 'USD', 1848.68, 0.00, 1848.68, 'false', '', '179', 'quickbook-FRN100000384-76', 'quickbook-FRN100000384-S179', NULL),
(6, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-12-14T23:17:35-08:00', '2017-12-14T23:17:41-08:00', '', 'Christine Mazurk', '696 Sand Isle Circle', '32082', 'Turtle Shores', '', '', '', 'Christine Mazurk', '', '', 'ACCREC', '3106', '2017-12-14T23:17:35-08:00', '2018-01-13', '', '', '', 'USD', 8735.56, 0.00, 8735.56, 'false', '', '178', 'quickbook-FRN100000384-75', 'quickbook-FRN100000384-S178', NULL),
(7, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-12-14T23:17:14-08:00', '2017-12-14T23:17:20-08:00', '', 'Christina Nagorski', '164 Spartina Ave.', '', '', '', '', '', 'Christina Nagorski', '', '', 'ACCREC', '3110', '2017-12-14T23:17:14-08:00', '2018-01-13', '', '', '', 'USD', 1782.28, 0.00, 1782.28, 'false', '', '177', 'quickbook-FRN100000384-74', 'quickbook-FRN100000384-S177', NULL),
(8, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-12-14T23:16:52-08:00', '2017-12-14T23:17:03-08:00', '', 'John and Betsy Pruess', '443 Segovia Rd', '32086', 'St Augustine', '', '', '', 'John and Betsy Pruess', '', '', 'ACCREC', '3116', '2017-12-14T23:16:52-08:00', '2018-01-13', '', '', '', 'USD', 1853.20, 0.00, 1853.20, 'false', '', '176', 'quickbook-FRN100000384-73', 'quickbook-FRN100000384-S176', NULL),
(9, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-12-14T23:16:30-08:00', '2017-12-14T23:16:36-08:00', '', 'Linda Phillips', '454 Ocean cay Blvd', '32080', 'St Augustine', '', '', '', 'Linda Phillips', '', '', 'ACCREC', '3119', '2017-12-14T23:16:30-08:00', '2018-01-13', '', '', '', 'USD', 3390.10, 0.00, 3390.10, 'false', '', '175', 'quickbook-FRN100000384-72', 'quickbook-FRN100000384-S175', NULL),
(10, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-12-14T23:16:16-08:00', '2017-12-14T23:16:22-08:00', '', 'Barbara Kirk', '108 Mainstreet seagrove', '32080', 'St. Augustine', '', '', '', 'Barbara Kirk', '', '', 'ACCREC', '3134', '2017-12-14T23:16:16-08:00', '2018-01-13', '', '', '', 'USD', 2036.31, 0.00, 2036.31, 'false', '', '174', 'quickbook-FRN100000384-71', 'quickbook-FRN100000384-S174', NULL),
(11, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-12-14T23:16:03-08:00', '2017-12-14T23:16:09-08:00', '', 'Mary Embree', '5161 La Strada Place', '', 'Coquina crossing', '', '', '', 'Mary Embree', '', '', 'ACCREC', '3135', '2017-12-14T23:16:03-08:00', '2018-01-13', '', '', '', 'USD', 1305.36, 0.00, 1305.36, 'false', '', '173', 'quickbook-FRN100000384-70', 'quickbook-FRN100000384-S173', NULL),
(12, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-12-14T23:15:51-08:00', '2017-12-14T23:15:56-08:00', '', 'Susie Gray', '138 Moore St.', '32084', '', '', '', '', 'Susie Gray', '', '', 'ACCREC', '3138', '2017-12-14T23:15:51-08:00', '2018-01-13', '', '', '', 'USD', 525.76, 0.00, 525.76, 'false', '', '172', 'quickbook-FRN100000384-69', 'quickbook-FRN100000384-S172', NULL),
(13, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-12-14T23:15:38-08:00', '2017-12-14T23:15:44-08:00', '', 'Steve Conway', '9 1/2 Riberia street', '', 'St Augustine', '', '', '', 'Steve Conway', '', '', 'ACCREC', '3151', '2017-12-14T23:15:38-08:00', '2018-01-13', '', '', '', 'USD', 295.74, 0.00, 295.74, 'false', '', '171', 'quickbook-FRN100000384-68', 'quickbook-FRN100000384-S171', NULL),
(14, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-12-14T23:15:26-08:00', '2017-12-14T23:15:31-08:00', '', 'Tom Buck', '', '', '', '', '', '', 'Tom Buck', '', '', 'ACCREC', '3169', '2017-12-14T23:15:26-08:00', '2018-01-13', '', '', '', 'USD', 610.56, 0.00, 610.56, 'false', '', '170', 'quickbook-FRN100000384-66', 'quickbook-FRN100000384-S170', NULL),
(15, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-12-14T23:15:14-08:00', '2017-12-14T23:15:20-08:00', '', 'Rick and Susan Reckert', '', '', '', '', '', '', 'Rick and Susan Reckert', '', '', 'ACCREC', '3184', '2017-12-14T23:15:14-08:00', '2018-01-13', '', '', '', 'USD', 1580.78, 0.00, 1580.78, 'false', '', '169', 'quickbook-FRN100000384-67', 'quickbook-FRN100000384-S169', NULL),
(16, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-12-14T23:15:02-08:00', '2017-12-14T23:15:07-08:00', '', 'Tom Buck', '', '', '', '', '', '', 'Tom Buck', '', '', 'ACCREC', '3392', '2017-12-14T23:15:02-08:00', '2018-01-13', '', '', '', 'USD', 3473.86, 0.00, 3473.86, 'false', '', '168', 'quickbook-FRN100000384-66', 'quickbook-FRN100000384-S168', NULL),
(17, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-12-14T23:14:48-08:00', '2017-12-14T23:14:54-08:00', '', 'test test', 'test 2', '234234', 'test 2', '', '', '', 'test test', '', '', 'ACCREC', '3568', '2017-12-14T23:14:48-08:00', '2018-01-13', '', '', '', 'USD', 0.00, 0.00, 0.00, 'false', '', '167', 'quickbook-FRN100000384-65', 'quickbook-FRN100000384-S167', NULL),
(18, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-12-14T23:14:37-08:00', '2017-12-14T23:14:42-08:00', '', 'Jack Edwards', '8933 W TOM RD', '85016', 'Phoenix', '', '', '', 'Jack Edwards', '', '', 'ACCREC', '11527', '2017-12-14T23:14:37-08:00', '2018-01-13', '', '', '', 'USD', 3064.25, 0.00, 3064.25, 'false', '', '166', 'quickbook-FRN100000384-64', 'quickbook-FRN100000384-S166', NULL),
(19, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-06-30T22:29:09-07:00', '2017-11-22T01:27:25-08:00', '', 'Bill 546464 Lucchini', '12 Ocean Dr.', '94213', 'Half Moon Bay', '', '', '', 'Bill 546464 Lucchini', '', 'Surf@Intuit.com', 'ACCREC', '1048', '2017-06-30T22:29:09-07:00', '2017-07-30', '', '', '', 'USD', 80.00, 0.00, 80.00, 'false', '', '155', 'quickbook-FRN100000384-2', 'quickbook-FRN100000384-S155', NULL),
(20, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-09-01T22:52:26-07:00', '2017-11-09T02:49:22-08:00', '', 'Amy qwerty Lauterbach', '4581 Finch St.', '94326', 'Bayshore', 'India', '', '', 'Amy qwerty Lauterbach', 'India', 'Birds@Intuit.com', 'ACCREC', '1051', '2017-09-01T22:52:26-07:00', '2017-12-31', '', '', '', 'USD', 4400.00, 0.00, 4400.00, 'false', '', '162', 'quickbook-FRN100000384-1', 'quickbook-FRN100000384-S162', NULL),
(21, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-09-01T23:47:08-07:00', '2017-09-02T01:00:42-07:00', '', 'Diego Rodriguez', '321 Channing', '94303', 'Palo Alto', '', '', '', 'Diego Rodriguez', '', 'Diego@Rodriguez.com', 'ACCREC', '1054', '2017-09-01T23:47:08-07:00', '2017-10-01', '', '', '', 'USD', 360.00, 0.00, 360.00, 'false', '', '165', 'quickbook-FRN100000384-4', 'quickbook-FRN100000384-S165', NULL),
(22, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-09-01T23:38:36-07:00', '2017-09-01T23:46:21-07:00', '', '', '', '', '', '', '', '', '', '', 'kd@1wayit.com', 'ACCREC', '1053', '2017-09-01T23:38:36-07:00', '2017-10-01', '', '', '', 'USD', 302.40, 22.40, 302.40, 'false', '', '164', 'quickbook-FRN100000384-63', 'quickbook-FRN100000384-S164', NULL),
(23, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-09-01T23:36:48-07:00', '2017-09-01T23:36:48-07:00', '', 'Grace123 Pariente', '65 Ocean Dr.', '94213', 'Half Moon Bay', '', '', '', 'Grace123 Pariente', '', 'Cool_Cars@intuit.com', 'ACCREC', '1052', '2017-09-01T23:36:48-07:00', '2017-10-01', '', '', '', 'USD', 200.00, 0.00, 200.00, 'false', '', '163', 'quickbook-FRN100000384-3', 'quickbook-FRN100000384-S163', NULL),
(24, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-07-03T05:07:12-07:00', '2017-09-01T22:51:25-07:00', '', 'Karen Pye', '350 Mountain View Dr.', '07079', 'South Orange', '', '', '', 'Karen Pye', '', 'pyescakes123@intuit.com', 'ACCREC', '1049', '2017-07-03T05:07:12-07:00', '2017-08-02', '', '', '', 'USD', 200.00, 0.00, 200.00, 'false', '', '156', 'quickbook-FRN100000384-15', 'quickbook-FRN100000384-S156', NULL),
(25, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-21T14:49:30-07:00', '2017-08-31T02:18:02-07:00', '', 'Amy qwerty Lauterbach', '4581 Finch St.', '94326', 'Bayshore', 'India', '', '', 'Amy qwerty Lauterbach', 'India', 'Birds@Intuit.com', 'ACCREC', '1001', '2017-05-21T14:49:30-07:00', '2017-06-20', '', '', '', 'USD', 216.00, 16.00, 0.00, 'false', '', '9', 'quickbook-FRN100000384-1', 'quickbook-FRN100000384-S9', NULL),
(26, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-21T14:57:16-07:00', '2017-08-31T00:30:37-07:00', '', 'Diego Rodriguez', '321 Channing', '94303', 'Palo Alto', '', '', '', 'Diego Rodriguez', '', 'Diego@Rodriguez.com', 'ACCREC', '1002', '2017-05-21T14:57:16-07:00', '2017-03-08', '', '', '', 'USD', 550.00, 0.00, 0.00, 'false', '', '10', 'quickbook-FRN100000384-4', 'quickbook-FRN100000384-S10', NULL),
(27, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-08-21T20:57:33-07:00', '2017-08-21T20:57:33-07:00', '', 'Neeraj123 m123 kumar123', '#1234 sector 15123', '160015123', 'chandigarh', 'india', '', '', 'Neeraj123 m123 kumar123', 'india', 'aa@a.com', 'ACCREC', '1050', '2017-08-21T20:57:33-07:00', '2017-09-20', '', '', '', 'USD', 445.00, 0.00, 445.00, 'false', '', '159', 'quickbook-FRN100000384-58', 'quickbook-FRN100000384-S159', NULL),
(28, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-06-30T22:16:38-07:00', '2017-06-30T22:28:40-07:00', '', 'Amy qwerty Lauterbach', '4581 Finch St.', '94326', 'Bayshore', 'India', '', '', 'Amy qwerty Lauterbach', 'India', 'Birds@Intuit.com', 'ACCREC', '1047', '2017-06-30T22:16:38-07:00', '2017-07-30', '', '', '', 'USD', 182.00, 12.00, 182.00, 'false', '', '154', 'quickbook-FRN100000384-1', 'quickbook-FRN100000384-S154', NULL),
(29, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-06-30T05:19:13-07:00', '2017-06-30T22:16:15-07:00', '', 'Grace123 Pariente', '65 Ocean Dr.', '94213', 'Half Moon Bay', '', '', '', 'Grace123 Pariente', '', 'Cool_Cars@intuit.com', 'ACCREC', '1046', '2017-06-30T05:19:13-07:00', '2017-07-30', '', '', '', 'USD', 10000.00, 0.00, 10000.00, 'false', '', '153', 'quickbook-FRN100000384-3', 'quickbook-FRN100000384-S153', NULL),
(30, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-06-30T04:23:50-07:00', '2017-06-30T05:18:51-07:00', '', 'Amy qwerty Lauterbach', '4581 Finch St.', '94326', 'Bayshore', 'India', '', '', 'Amy qwerty Lauterbach', 'India', 'Birds@Intuit.com', 'ACCREC', '1044', '2017-06-30T04:23:50-07:00', '2017-07-30', '', '', '', 'USD', 700.00, 0.00, 700.00, 'false', '', '151', 'quickbook-FRN100000384-1', 'quickbook-FRN100000384-S151', NULL),
(31, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-06-30T04:36:39-07:00', '2017-06-30T04:37:03-07:00', '', 'Amy qwerty Lauterbach', '4581 Finch St.', '94326', 'Bayshore', 'India', '', '', 'Amy qwerty Lauterbach', 'India', 'Birds@Intuit.com', 'ACCREC', '1045', '2017-06-30T04:36:39-07:00', '2017-07-30', '', '', '', 'USD', 1500.00, 0.00, 1500.00, 'false', '', '152', 'quickbook-FRN100000384-1', 'quickbook-FRN100000384-S152', NULL),
(32, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-06-30T04:06:20-07:00', '2017-06-30T04:23:59-07:00', '', 'Amy qwerty Lauterbach', '4581 Finch St.', '94326', 'Bayshore', 'India', '', '', 'Amy qwerty Lauterbach', 'India', 'Birds@Intuit.com', 'ACCREC', '1042', '2017-06-30T04:06:20-07:00', '2017-07-30', '', '', '', 'USD', 700.00, 0.00, 700.00, 'false', '', '149', 'quickbook-FRN100000384-1', 'quickbook-FRN100000384-S149', NULL),
(33, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-06-30T04:13:50-07:00', '2017-06-30T04:13:50-07:00', '', 'Dylan Sollfrank', '', '', '', '', '', '', 'Dylan Sollfrank', '', '', 'ACCREC', '1043', '2017-06-30T04:13:50-07:00', '2017-07-30', '', '', '', 'USD', 2300.00, 0.00, 2300.00, 'false', '', '150', 'quickbook-FRN100000384-6', 'quickbook-FRN100000384-S150', NULL),
(34, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-06-30T03:51:51-07:00', '2017-06-30T04:08:02-07:00', '', 'Bill 546464 Lucchini', '12 Ocean Dr.', '94213', 'Half Moon Bay', '', '', '', 'Bill 546464 Lucchini', '', 'Surf@Intuit.com', 'ACCREC', '1040', '2017-06-30T03:51:51-07:00', '2017-07-30', '', '', '', 'USD', 200.00, 0.00, 200.00, 'false', '', '147', 'quickbook-FRN100000384-2', 'quickbook-FRN100000384-S147', NULL),
(35, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-06-30T03:57:51-07:00', '2017-06-30T03:57:51-07:00', '', 'Grace123 Pariente', '65 Ocean Dr.', '94213', 'Half Moon Bay', '', '', '', 'Grace123 Pariente', '', 'Cool_Cars@intuit.com', 'ACCREC', '1041', '2017-06-30T03:57:51-07:00', '2017-07-30', '', '', '', 'USD', 200.00, 0.00, 200.00, 'false', '', '148', 'quickbook-FRN100000384-3', 'quickbook-FRN100000384-S148', NULL),
(36, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-24T13:16:17-07:00', '2017-06-30T03:52:06-07:00', '', 'Russ Sonnenschein', '5647 Cypress Hill Ave.', '94303', 'Middlefield', '', '', '', 'Russ Sonnenschein', '', 'Familiystore@intuit.com', 'ACCREC', '1037', '2017-05-24T13:16:17-07:00', '2017-06-23', '', '', '', 'USD', 428.07, 26.82, 428.07, 'false', '', '130', 'quickbook-FRN100000384-24', 'quickbook-FRN100000384-S130', NULL),
(37, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-06-30T03:42:28-07:00', '2017-06-30T03:42:28-07:00', '', 'Amy qwerty Lauterbach', '4581 Finch St.', '94326', 'Bayshore', 'India', '', '', 'Amy qwerty Lauterbach', 'India', 'Birds@Intuit.com', 'ACCREC', '1039', '2017-06-30T03:42:28-07:00', '2017-07-30', '', '', '', 'USD', 1000.00, 0.00, 1000.00, 'false', '', '146', 'quickbook-FRN100000384-1', 'quickbook-FRN100000384-S146', NULL),
(38, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-23T13:45:12-07:00', '2017-06-30T02:51:15-07:00', '', 'Rondonuwu Fruit and Vegi', '847 California Ave.', '95021', 'San Jose', '', '', '', 'Rondonuwu Fruit and Vegi', '', 'Tony@Rondonuwu.com', 'ACCREC', '1034', '2017-05-23T13:45:12-07:00', '2017-06-22', '', '', '', 'USD', 924.00, 24.00, 924.00, 'false', '', '106', 'quickbook-FRN100000384-21', 'quickbook-FRN100000384-S106', NULL),
(39, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-06-28T22:33:33-07:00', '2017-06-30T02:50:49-07:00', '', 'Amy qwerty Lauterbach', '4581 Finch St.', '94326', 'Bayshore', 'India', '', '', 'Amy qwerty Lauterbach', 'India', 'Birds@Intuit.com', 'ACCREC', '1038', '2017-06-28T22:33:33-07:00', '2017-07-28', '', '', '', 'USD', 2775.00, 0.00, 2775.00, 'false', '', '145', 'quickbook-FRN100000384-1', 'quickbook-FRN100000384-S145', NULL),
(40, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-24T12:57:24-07:00', '2017-06-30T02:27:54-07:00', '', 'Mark Cho', '36 Willow Rd', '94304', 'Menlo Park', '', '', '', 'Mark Cho', '', 'Mark@Cho.com', 'ACCREC', '1035', '2017-05-24T12:57:24-07:00', '2017-06-23', '', '', '', 'USD', 972.00, 72.00, 972.00, 'false', '', '119', 'quickbook-FRN100000384-17', 'quickbook-FRN100000384-S119', NULL),
(41, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-22T15:28:08-07:00', '2017-06-29T23:56:48-07:00', '', 'Katsuyuki Yanagawa', '898 Elm St.', '07040', 'Maplewood', '', '', '', 'Katsuyuki Yanagawa', '', 'Sushi@intuit.com', 'ACCREC', '1017', '2017-05-22T15:28:08-07:00', '2017-06-07', '', '', '', 'USD', 400.00, 0.00, 360.00, 'false', '', '63', 'quickbook-FRN100000384-25', 'quickbook-FRN100000384-S63', NULL),
(42, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-24T13:15:36-07:00', '2017-06-29T23:56:24-07:00', '', 'Sasha Tillou', '370 Easy St.', '94482', 'Middlefield', '', '', '', 'Sasha Tillou', '', 'Sporting_goods@intuit.com', 'ACCREC', '1036', '2017-05-24T13:15:36-07:00', '2017-06-23', '', '', '', 'USD', 740.00, 0.00, 740.00, 'false', '', '129', 'quickbook-FRN100000384-8', 'quickbook-FRN100000384-S129', NULL),
(43, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-22T11:16:52-07:00', '2017-06-29T05:49:01-07:00', '', 'Shara Barnett', '19 Main St.', '94303', 'Middlefield', '', '', '', 'Shara Barnett', '', 'Design@intuit.com', 'ACCREC', '1012', '2017-05-22T11:16:52-07:00', '2017-06-09', '', '', '', 'USD', 3510.00, 0.00, 3510.00, 'false', '', '39', 'quickbook-FRN100000384-23', 'quickbook-FRN100000384-S39', NULL),
(44, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-23T13:30:49-07:00', '2017-06-29T05:31:04-07:00', '', 'Sasha Tillou', '370 Easy St.', '94482', 'Middlefield', '', '', '', 'Sasha Tillou', '', 'Sporting_goods@intuit.com', 'ACCREC', '1031', '2017-05-23T13:30:49-07:00', '2017-04-08', '', '', '', 'USD', 477.00, 22.00, 90.00, 'false', '', '96', 'quickbook-FRN100000384-8', 'quickbook-FRN100000384-S96', NULL),
(45, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-21T15:04:04-07:00', '2017-05-24T12:59:21-07:00', '', 'Grace123 Pariente', '65 Ocean Dr.', '94213', 'Half Moon Bay', '', '', '', 'Grace123 Pariente', '', 'Cool_Cars@intuit.com', 'ACCREC', '1004', '2017-05-21T15:04:04-07:00', '2017-06-11', '', '', '', 'USD', 2369.52, 175.52, 0.00, 'false', '', '12', 'quickbook-FRN100000384-3', 'quickbook-FRN100000384-S12', NULL),
(46, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-23T13:41:59-07:00', '2017-05-23T13:42:08-07:00', '', 'Geeta Kalapatapu', '1987 Main St.', '94303', 'Middlefield', '', '', '', 'Geeta Kalapatapu', '', 'Geeta@Kalapatapu.com', 'ACCREC', '1033', '2017-05-23T13:41:59-07:00', '2017-06-22', '', '', '', 'USD', 629.10, 46.60, 629.10, 'false', '', '103', 'quickbook-FRN100000384-10', 'quickbook-FRN100000384-S103', NULL),
(47, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-23T12:40:06-07:00', '2017-05-23T13:39:32-07:00', '', 'Amy qwerty Lauterbach', '4581 Finch St.', '94326', 'Bayshore', 'India', '', '', 'Amy qwerty Lauterbach', 'India', 'Birds@Intuit.com', 'ACCREC', '1021', '2017-05-23T12:40:06-07:00', '2017-06-01', '', '', '', 'USD', 459.00, 34.00, 239.00, 'false', '', '67', 'quickbook-FRN100000384-1', 'quickbook-FRN100000384-S67', NULL),
(48, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-23T13:36:31-07:00', '2017-05-23T13:36:31-07:00', '', 'Travis Waldron', '78 First St.', '94304', 'Monlo Park', '', '', '', 'Travis Waldron', '', 'Travis@Waldron.com', 'ACCREC', '1032', '2017-05-23T13:36:31-07:00', '2017-06-20', '', '', '', 'USD', 414.72, 30.72, 414.72, 'false', '', '99', 'quickbook-FRN100000384-26', 'quickbook-FRN100000384-S99', NULL),
(49, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-22T11:24:29-07:00', '2017-05-23T13:35:42-07:00', '', 'Travis Waldron', '78 First St.', '94304', 'Monlo Park', '', '', '', 'Travis Waldron', '', 'Travis@Waldron.com', 'ACCREC', '1013', '2017-05-22T11:24:29-07:00', '2017-06-11', '', '', '', 'USD', 81.00, 6.00, 0.00, 'false', '', '42', 'quickbook-FRN100000384-26', 'quickbook-FRN100000384-S42', NULL),
(50, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-23T13:29:56-07:00', '2017-05-23T13:31:45-07:00', '', 'Sasha Tillou', '370 Easy St.', '94482', 'Middlefield', '', '', '', 'Sasha Tillou', '', 'Sporting_goods@intuit.com', 'ACCREC', '1030', '2017-05-23T13:29:56-07:00', '2017-03-08', '', '', '', 'USD', 226.75, 10.50, 0.00, 'false', '', '95', 'quickbook-FRN100000384-8', 'quickbook-FRN100000384-S95', NULL),
(51, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-23T13:27:49-07:00', '2017-05-23T13:28:29-07:00', '', 'Peter Dukes', '25 Court St.', '85719', 'Tucson', '', '', '', 'Peter Dukes', '', 'Dukes_bball@intuit.com', 'ACCREC', '1029', '2017-05-23T13:27:49-07:00', '2017-05-06', '', '', '', 'USD', 460.40, 38.40, 0.00, 'false', '', '93', 'quickbook-FRN100000384-5', 'quickbook-FRN100000384-S93', NULL),
(52, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-23T12:41:24-07:00', '2017-05-23T13:25:43-07:00', '', 'Jeff Chin', '12 Willow Rd.', '94305', 'Menlo Park', '', '', '', 'Jeff Chin', '', 'Jalopies@intuit.com', 'ACCREC', '1022', '2017-05-23T12:41:24-07:00', '2017-06-01', '', '', '', 'USD', 81.00, 6.00, 81.00, 'false', '', '68', 'quickbook-FRN100000384-12', 'quickbook-FRN100000384-S68', NULL),
(53, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-21T15:05:48-07:00', '2017-05-23T13:23:52-07:00', '', 'Amelia', '370 Easy St.', '94482', 'Middlefield', '', '', '', 'Amelia', '', 'Sporting_goods@intuit.com', 'ACCREC', '1005', '2017-05-21T15:05:48-07:00', '2017-06-14', '', '', '', 'USD', 54.00, 4.00, 4.00, 'false', '', '13', 'quickbook-FRN100000384-9', 'quickbook-FRN100000384-S13', NULL),
(54, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-21T15:06:59-07:00', '2017-05-23T13:23:11-07:00', '', 'Amelia', '370 Easy St.', '94482', 'Middlefield', '', '', '', 'Amelia', '', 'Sporting_goods@intuit.com', 'ACCREC', '1006', '2017-05-21T15:06:59-07:00', '2017-05-15', '', '', '', 'USD', 86.40, 6.40, 0.00, 'false', '', '14', 'quickbook-FRN100000384-9', 'quickbook-FRN100000384-S14', NULL),
(55, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-23T13:22:13-07:00', '2017-05-23T13:22:27-07:00', '', 'Amelia', '370 Easy St.', '94482', 'Middlefield', '', '', '', 'Amelia', '', 'Sporting_goods@intuit.com', 'ACCREC', '1028', '2017-05-23T13:22:13-07:00', '2017-05-06', '', '', '', 'USD', 81.00, 6.00, 81.00, 'false', '', '92', 'quickbook-FRN100000384-9', 'quickbook-FRN100000384-S92', NULL),
(56, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-23T12:54:08-07:00', '2017-05-23T12:54:08-07:00', '', 'Bill 546464 Lucchini', '12 Ocean Dr.', '94213', 'Half Moon Bay', '', '', '', 'Bill 546464 Lucchini', '', 'Surf@Intuit.com', 'ACCREC', '1027', '2017-05-23T12:54:08-07:00', '2017-05-06', '', '', '', 'USD', 85.00, 0.00, 85.00, 'false', '', '75', 'quickbook-FRN100000384-2', 'quickbook-FRN100000384-S75', NULL),
(57, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-23T12:49:30-07:00', '2017-05-23T12:51:28-07:00', '', 'Amy qwerty Lauterbach', '4581 Finch St.', '94326', 'Bayshore', 'India', '', '', 'Amy qwerty Lauterbach', 'India', 'Birds@Intuit.com', 'ACCREC', '1025', '2017-05-23T12:49:30-07:00', '2017-05-06', '', '', '', 'USD', 205.00, 0.00, 0.00, 'false', '', '71', 'quickbook-FRN100000384-1', 'quickbook-FRN100000384-S71', NULL),
(58, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-23T12:44:45-07:00', '2017-05-23T12:44:45-07:00', '', 'Stephanie Martini', '500 Red Rock Rd.', '94326', 'Bayshore', '', '', '', 'Stephanie Martini', '', 'qbwebsamplecompany@yahoo.com', 'ACCREC', '1024', '2017-05-23T12:44:45-07:00', '2017-04-15', '', '', '', 'USD', 156.00, 0.00, 156.00, 'false', '', '70', 'quickbook-FRN100000384-20', 'quickbook-FRN100000384-S70', NULL),
(59, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-23T12:42:59-07:00', '2017-05-23T12:42:59-07:00', '', 'Stephanie Martini', '500 Red Rock Rd.', '94326', 'Bayshore', '', '', '', 'Stephanie Martini', '', 'qbwebsamplecompany@yahoo.com', 'ACCREC', '1023', '2017-05-23T12:42:59-07:00', '2017-06-21', '', '', '', 'USD', 70.00, 0.00, 70.00, 'false', '', '69', 'quickbook-FRN100000384-20', 'quickbook-FRN100000384-S69', NULL),
(60, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-22T15:29:27-07:00', '2017-05-23T12:18:01-07:00', '', 'Katsuyuki Yanagawa', '898 Elm St.', '07040', 'Maplewood', '', '', '', 'Katsuyuki Yanagawa', '', 'Sushi@intuit.com', 'ACCREC', '1019', '2017-05-22T15:29:27-07:00', '2017-06-21', '', '', '', 'USD', 80.00, 0.00, 80.00, 'false', '', '65', 'quickbook-FRN100000384-25', 'quickbook-FRN100000384-S65', NULL),
(61, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-22T15:29:00-07:00', '2017-05-22T15:29:00-07:00', '', 'Katsuyuki Yanagawa', '898 Elm St.', '07040', 'Maplewood', '', '', '', 'Katsuyuki Yanagawa', '', 'Sushi@intuit.com', 'ACCREC', '1018', '2017-05-22T15:29:00-07:00', '2017-06-14', '', '', '', 'USD', 80.00, 0.00, 80.00, 'false', '', '64', 'quickbook-FRN100000384-25', 'quickbook-FRN100000384-S64', NULL),
(62, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-22T15:18:18-07:00', '2017-05-22T15:18:18-07:00', '', 'Kathy Kuplis', '789 Sugar Lane', '94303', 'Middlefield', '', '', '', 'Kathy Kuplis', '', 'qbwebsamplecompany@yahoo.com', 'ACCREC', '1016', '2017-05-22T15:18:18-07:00', '2017-05-05', '', '', '', 'USD', 75.00, 0.00, 75.00, 'false', '', '60', 'quickbook-FRN100000384-16', 'quickbook-FRN100000384-S60', NULL),
(63, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-22T11:43:20-07:00', '2017-05-22T11:43:20-07:00', '', 'Kathy Paulsen', '900 Main St.', '94303', 'Middlefield', '', '', '', 'Kathy Paulsen', '', 'Medical@intuit.com', 'ACCREC', '1015', '2017-05-22T11:43:20-07:00', '2017-06-21', '', '', '', 'USD', 954.75, 0.00, 954.75, 'false', '', '49', 'quickbook-FRN100000384-18', 'quickbook-FRN100000384-S49', NULL),
(64, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-21T15:38:07-07:00', '2017-05-22T11:17:33-07:00', '', 'Travis Waldron', '78 First St.', '94304', 'Monlo Park', '', '', '', 'Travis Waldron', '', 'Travis@Waldron.com', 'ACCREC', '1009', '2017-05-21T15:38:07-07:00', '2017-06-20', '', '', '', 'USD', 103.55, 0.00, 0.00, 'false', '', '27', 'quickbook-FRN100000384-26', 'quickbook-FRN100000384-S27', NULL),
(65, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-22T11:09:08-07:00', '2017-05-22T11:09:08-07:00', '', 'Nicola Weiskopf', '45612 Main St.', '94326', 'Bayshore', '', '', '', 'Nicola Weiskopf', '', 'Consulting@intuit.com', 'ACCREC', '1010', '2017-05-22T11:09:08-07:00', '2017-06-21', '', '', '', 'USD', 375.00, 0.00, 375.00, 'false', '', '34', 'quickbook-FRN100000384-29', 'quickbook-FRN100000384-S34', NULL),
(66, 'FRN100000384', 'quickbook-FRN100000384', '', '2018-01-03', '2017-05-21T15:10:40-07:00', '2017-05-22T11:06:49-07:00', '', 'John Melton', '85 Pine St.', '94304', 'Menlo Park', '', '', '', 'John Melton', '', 'John@Melton.com', 'ACCREC', '1007', '2017-05-21T15:10:40-07:00', '2017-05-29', '', '', '', 'USD', 750.00, 0.00, 450.00, 'false', '', '16', 'quickbook-FRN100000384-13', 'quickbook-FRN100000384-S16', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `syncinvoice_item`
--

CREATE TABLE `syncinvoice_item` (
  `id` int(11) UNSIGNED NOT NULL,
  `frenns_id` varchar(255) NOT NULL DEFAULT '',
  `unique_frenns_id` varchar(200) DEFAULT NULL,
  `invoice_number` varchar(255) NOT NULL DEFAULT '',
  `line_number` varchar(255) NOT NULL DEFAULT '',
  `product_code` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `qty` int(11) NOT NULL,
  `rate` float(11,2) NOT NULL DEFAULT '0.00',
  `amount_net` float(11,2) NOT NULL DEFAULT '0.00',
  `invoiceline_vat_amount` float(11,2) NOT NULL DEFAULT '0.00',
  `amount_total` float(11,2) NOT NULL DEFAULT '0.00',
  `invoice_id` varchar(255) DEFAULT NULL,
  `updateId` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `syncinvoice_item`
--

INSERT INTO `syncinvoice_item` (`id`, `frenns_id`, `unique_frenns_id`, `invoice_number`, `line_number`, `product_code`, `description`, `qty`, `rate`, `amount_net`, `invoiceline_vat_amount`, `amount_total`, `invoice_id`, `updateId`) VALUES
(1, 'FRN100000384', 'quickbook-FRN100000384', '3033', '1', '', '', 0, 0.00, 1977.16, 0.00, 1977.16, '183', 'quickbook-FRN100000384-S183-1'),
(2, 'FRN100000384', 'quickbook-FRN100000384', '3034', '1', '', '', 0, 0.00, 1830.70, 0.00, 1830.70, '182', 'quickbook-FRN100000384-S182-1'),
(3, 'FRN100000384', 'quickbook-FRN100000384', '3072', '1', '', '', 0, 0.00, 1169.56, 0.00, 1169.56, '181', 'quickbook-FRN100000384-S181-1'),
(4, 'FRN100000384', 'quickbook-FRN100000384', '3100', '1', '', '', 0, 0.00, 811.94, 0.00, 811.94, '180', 'quickbook-FRN100000384-S180-1'),
(5, 'FRN100000384', 'quickbook-FRN100000384', '3105', '1', '', '', 0, 0.00, 1848.68, 0.00, 1848.68, '179', 'quickbook-FRN100000384-S179-1'),
(6, 'FRN100000384', 'quickbook-FRN100000384', '3106', '1', '', '', 0, 0.00, 8735.56, 0.00, 8735.56, '178', 'quickbook-FRN100000384-S178-1'),
(7, 'FRN100000384', 'quickbook-FRN100000384', '3110', '1', '', '', 0, 0.00, 1782.28, 0.00, 1782.28, '177', 'quickbook-FRN100000384-S177-1'),
(8, 'FRN100000384', 'quickbook-FRN100000384', '3116', '1', '', '', 0, 0.00, 1853.20, 0.00, 1853.20, '176', 'quickbook-FRN100000384-S176-1'),
(9, 'FRN100000384', 'quickbook-FRN100000384', '3119', '1', '', '', 0, 0.00, 3390.10, 0.00, 3390.10, '175', 'quickbook-FRN100000384-S175-1'),
(10, 'FRN100000384', 'quickbook-FRN100000384', '3134', '1', '', '', 0, 0.00, 2036.31, 0.00, 2036.31, '174', 'quickbook-FRN100000384-S174-1'),
(11, 'FRN100000384', 'quickbook-FRN100000384', '3135', '1', '', '', 0, 0.00, 1305.36, 0.00, 1305.36, '173', 'quickbook-FRN100000384-S173-1'),
(12, 'FRN100000384', 'quickbook-FRN100000384', '3138', '1', '', '', 0, 0.00, 525.76, 0.00, 525.76, '172', 'quickbook-FRN100000384-S172-1'),
(13, 'FRN100000384', 'quickbook-FRN100000384', '3151', '1', '', '', 0, 0.00, 295.74, 0.00, 295.74, '171', 'quickbook-FRN100000384-S171-1'),
(14, 'FRN100000384', 'quickbook-FRN100000384', '3169', '1', '', '', 0, 0.00, 610.56, 0.00, 610.56, '170', 'quickbook-FRN100000384-S170-1'),
(15, 'FRN100000384', 'quickbook-FRN100000384', '3184', '1', '', '', 0, 0.00, 1580.78, 0.00, 1580.78, '169', 'quickbook-FRN100000384-S169-1'),
(16, 'FRN100000384', 'quickbook-FRN100000384', '3392', '1', '', '', 0, 0.00, 3473.86, 0.00, 3473.86, '168', 'quickbook-FRN100000384-S168-1'),
(17, 'FRN100000384', 'quickbook-FRN100000384', '3568', '1', '', '', 0, 0.00, 0.00, 0.00, 0.00, '167', 'quickbook-FRN100000384-S167-1'),
(18, 'FRN100000384', 'quickbook-FRN100000384', '11527', '1', '', '', 0, 0.00, 3064.25, 0.00, 3064.25, '166', 'quickbook-FRN100000384-S166-1'),
(19, 'FRN100000384', 'quickbook-FRN100000384', '1048', '1', '', '', 1, 10.00, 10.00, 0.00, 10.00, '155', 'quickbook-FRN100000384-S155-1'),
(20, 'FRN100000384', 'quickbook-FRN100000384', '1048', '2', '', '', 1, 20.00, 20.00, 0.00, 20.00, '155', 'quickbook-FRN100000384-S155-2'),
(21, 'FRN100000384', 'quickbook-FRN100000384', '1048', '3', '', '', 1, 50.00, 50.00, 0.00, 50.00, '155', 'quickbook-FRN100000384-S155-3'),
(22, 'FRN100000384', 'quickbook-FRN100000384', '1051', '1', '', '', 2, 1200.00, 2400.00, 0.00, 2400.00, '162', 'quickbook-FRN100000384-S162-1'),
(23, 'FRN100000384', 'quickbook-FRN100000384', '1051', '2', '', '', 2, 1000.00, 2000.00, 0.00, 2000.00, '162', 'quickbook-FRN100000384-S162-2'),
(24, 'FRN100000384', 'quickbook-FRN100000384', '1054', '1', '', '', 1, 155.00, 155.00, 0.00, 155.00, '165', 'quickbook-FRN100000384-S165-1'),
(25, 'FRN100000384', 'quickbook-FRN100000384', '1054', '2', '', '', 1, 50.00, 50.00, 0.00, 50.00, '165', 'quickbook-FRN100000384-S165-2'),
(26, 'FRN100000384', 'quickbook-FRN100000384', '1054', '3', '', '', 1, 100.00, 100.00, 0.00, 100.00, '165', 'quickbook-FRN100000384-S165-3'),
(27, 'FRN100000384', 'quickbook-FRN100000384', '1054', '4', '', '', 1, 55.00, 55.00, 0.00, 55.00, '165', 'quickbook-FRN100000384-S165-4'),
(28, 'FRN100000384', 'quickbook-FRN100000384', '1053', '1', '', '', 5, 50.00, 250.00, 0.00, 250.00, '164', 'quickbook-FRN100000384-S164-1'),
(29, 'FRN100000384', 'quickbook-FRN100000384', '1053', '3', '', '', 2, 15.00, 30.00, 0.00, 30.00, '164', 'quickbook-FRN100000384-S164-3'),
(30, 'FRN100000384', 'quickbook-FRN100000384', '1052', '1', '', '', 1, 50.00, 50.00, 0.00, 50.00, '163', 'quickbook-FRN100000384-S163-1'),
(31, 'FRN100000384', 'quickbook-FRN100000384', '1052', '2', '', '', 1, 150.00, 150.00, 0.00, 150.00, '163', 'quickbook-FRN100000384-S163-2'),
(32, 'FRN100000384', 'quickbook-FRN100000384', '1049', '1', '', '', 2, 40.00, 80.00, 0.00, 80.00, '156', 'quickbook-FRN100000384-S156-1'),
(33, 'FRN100000384', 'quickbook-FRN100000384', '1049', '2', '', '', 3, 40.00, 120.00, 0.00, 120.00, '156', 'quickbook-FRN100000384-S156-2'),
(34, 'FRN100000384', 'quickbook-FRN100000384', '1001', '1', '', '', 4, 25.00, 100.00, 0.00, 100.00, '9', 'quickbook-FRN100000384-S9-1'),
(35, 'FRN100000384', 'quickbook-FRN100000384', '1001', '3', '', '', 5, 20.00, 100.00, 0.00, 100.00, '9', 'quickbook-FRN100000384-S9-3'),
(36, 'FRN100000384', 'quickbook-FRN100000384', '1002', '1', '', '', 4, 100.00, 400.00, 0.00, 400.00, '10', 'quickbook-FRN100000384-S10-1'),
(37, 'FRN100000384', 'quickbook-FRN100000384', '1002', '2', '', '', 1, 150.00, 150.00, 0.00, 150.00, '10', 'quickbook-FRN100000384-S10-2'),
(38, 'FRN100000384', 'quickbook-FRN100000384', '1050', '1', '', '', 5, 75.00, 375.00, 0.00, 375.00, '159', 'quickbook-FRN100000384-S159-1'),
(39, 'FRN100000384', 'quickbook-FRN100000384', '1050', '2', '', '', 2, 35.00, 70.00, 0.00, 70.00, '159', 'quickbook-FRN100000384-S159-2'),
(40, 'FRN100000384', 'quickbook-FRN100000384', '1047', '1', '', '', 1, 150.00, 150.00, 0.00, 150.00, '154', 'quickbook-FRN100000384-S154-1'),
(41, 'FRN100000384', 'quickbook-FRN100000384', '1047', '3', '', '', 1, 20.00, 20.00, 0.00, 20.00, '154', 'quickbook-FRN100000384-S154-3'),
(42, 'FRN100000384', 'quickbook-FRN100000384', '1046', '1', '', '', 100, 100.00, 10000.00, 0.00, 10000.00, '153', 'quickbook-FRN100000384-S153-1'),
(43, 'FRN100000384', 'quickbook-FRN100000384', '1044', '1', '', '', 1, 700.00, 700.00, 0.00, 700.00, '151', 'quickbook-FRN100000384-S151-1'),
(44, 'FRN100000384', 'quickbook-FRN100000384', '1045', '1', '', '', 1, 1500.00, 1500.00, 0.00, 1500.00, '152', 'quickbook-FRN100000384-S152-1'),
(45, 'FRN100000384', 'quickbook-FRN100000384', '1042', '1', '', '', 1, 700.00, 700.00, 0.00, 700.00, '149', 'quickbook-FRN100000384-S149-1'),
(46, 'FRN100000384', 'quickbook-FRN100000384', '1043', '1', '', '', 1, 2300.00, 2300.00, 0.00, 2300.00, '150', 'quickbook-FRN100000384-S150-1'),
(47, 'FRN100000384', 'quickbook-FRN100000384', '1040', '1', '', '', 1, 200.00, 200.00, 0.00, 200.00, '147', 'quickbook-FRN100000384-S147-1'),
(48, 'FRN100000384', 'quickbook-FRN100000384', '1041', '1', '', '', 1, 200.00, 200.00, 0.00, 200.00, '148', 'quickbook-FRN100000384-S148-1'),
(49, 'FRN100000384', 'quickbook-FRN100000384', '1037', '1', '', '', 1, 275.00, 275.00, 0.00, 275.00, '130', 'quickbook-FRN100000384-S130-1'),
(50, 'FRN100000384', 'quickbook-FRN100000384', '1037', '2', '', '', 1, 12.75, 12.75, 0.00, 12.75, '130', 'quickbook-FRN100000384-S130-2'),
(51, 'FRN100000384', 'quickbook-FRN100000384', '1037', '3', '', '', 5, 9.50, 47.50, 0.00, 47.50, '130', 'quickbook-FRN100000384-S130-3'),
(52, 'FRN100000384', 'quickbook-FRN100000384', '1037', '5', '', '', 1, 66.00, 66.00, 0.00, 66.00, '130', 'quickbook-FRN100000384-S130-5'),
(53, 'FRN100000384', 'quickbook-FRN100000384', '1039', '1', '', '', 1, 1000.00, 1000.00, 0.00, 1000.00, '146', 'quickbook-FRN100000384-S146-1'),
(54, 'FRN100000384', 'quickbook-FRN100000384', '1034', '1', '', '', 2, 300.00, 600.00, 0.00, 600.00, '106', 'quickbook-FRN100000384-S106-1'),
(55, 'FRN100000384', 'quickbook-FRN100000384', '1034', '2', '', '', 3, 100.00, 300.00, 0.00, 300.00, '106', 'quickbook-FRN100000384-S106-2'),
(56, 'FRN100000384', 'quickbook-FRN100000384', '1038', '1', '', '', 5, 555.00, 2775.00, 0.00, 2775.00, '145', 'quickbook-FRN100000384-S145-1'),
(57, 'FRN100000384', 'quickbook-FRN100000384', '1035', '1', '', '', 1, 100.00, 100.00, 0.00, 100.00, '119', 'quickbook-FRN100000384-S119-1'),
(58, 'FRN100000384', 'quickbook-FRN100000384', '1035', '2', '', '', 4, 200.00, 800.00, 0.00, 800.00, '119', 'quickbook-FRN100000384-S119-2'),
(59, 'FRN100000384', 'quickbook-FRN100000384', '1017', '1', '', '', 4, 100.00, 400.00, 0.00, 400.00, '63', 'quickbook-FRN100000384-S63-1'),
(60, 'FRN100000384', 'quickbook-FRN100000384', '1036', '1', '', '', 5, 10.00, 50.00, 0.00, 50.00, '129', 'quickbook-FRN100000384-S129-1'),
(61, 'FRN100000384', 'quickbook-FRN100000384', '1036', '2', '', '', 5, 10.00, 50.00, 0.00, 50.00, '129', 'quickbook-FRN100000384-S129-2'),
(62, 'FRN100000384', 'quickbook-FRN100000384', '1036', '3', '', '', 4, 100.00, 350.00, 0.00, 350.00, '129', 'quickbook-FRN100000384-S129-3'),
(63, 'FRN100000384', 'quickbook-FRN100000384', '1036', '4', '', '', 1, 275.00, 275.00, 0.00, 275.00, '129', 'quickbook-FRN100000384-S129-4'),
(64, 'FRN100000384', 'quickbook-FRN100000384', '1036', '5', '', '', 1, 15.00, 15.00, 0.00, 15.00, '129', 'quickbook-FRN100000384-S129-5'),
(65, 'FRN100000384', 'quickbook-FRN100000384', '1012', '1', '', '', 15, 200.00, 3000.00, 0.00, 3000.00, '39', 'quickbook-FRN100000384-S39-1'),
(66, 'FRN100000384', 'quickbook-FRN100000384', '1012', '2', '', '', 1, 500.00, 500.00, 0.00, 500.00, '39', 'quickbook-FRN100000384-S39-2'),
(67, 'FRN100000384', 'quickbook-FRN100000384', '1012', '4', '', '', 1, 150.00, 150.00, 0.00, 150.00, '39', 'quickbook-FRN100000384-S39-4'),
(68, 'FRN100000384', 'quickbook-FRN100000384', '1012', '5', '', '', 1, 40.00, 40.00, 0.00, 40.00, '39', 'quickbook-FRN100000384-S39-5'),
(69, 'FRN100000384', 'quickbook-FRN100000384', '1012', '6', '', '', 1, 50.00, 50.00, 0.00, 50.00, '39', 'quickbook-FRN100000384-S39-6'),
(70, 'FRN100000384', 'quickbook-FRN100000384', '1012', '7', '', '', 1, 60.00, 60.00, 0.00, 60.00, '39', 'quickbook-FRN100000384-S39-7'),
(71, 'FRN100000384', 'quickbook-FRN100000384', '1012', '8', '', '', 1, 100.00, 100.00, 0.00, 100.00, '39', 'quickbook-FRN100000384-S39-8'),
(72, 'FRN100000384', 'quickbook-FRN100000384', '1031', '1', '', '', 3, 60.00, 180.00, 0.00, 180.00, '96', 'quickbook-FRN100000384-S96-1'),
(73, 'FRN100000384', 'quickbook-FRN100000384', '1031', '2', '', '', 1, 275.00, 275.00, 0.00, 275.00, '96', 'quickbook-FRN100000384-S96-2'),
(74, 'FRN100000384', 'quickbook-FRN100000384', '1004', '1', '', '', 10, 2.00, 20.00, 0.00, 20.00, '12', 'quickbook-FRN100000384-S12-1'),
(75, 'FRN100000384', 'quickbook-FRN100000384', '1004', '2', '', '', 6, 4.00, 24.00, 0.00, 24.00, '12', 'quickbook-FRN100000384-S12-2'),
(76, 'FRN100000384', 'quickbook-FRN100000384', '1004', '3', '', '', 50, 35.00, 1750.00, 0.00, 1750.00, '12', 'quickbook-FRN100000384-S12-3'),
(77, 'FRN100000384', 'quickbook-FRN100000384', '1004', '4', '', '', 8, 50.00, 400.00, 0.00, 400.00, '12', 'quickbook-FRN100000384-S12-4'),
(78, 'FRN100000384', 'quickbook-FRN100000384', '1033', '1', '', '', 1, 275.00, 275.00, 0.00, 275.00, '103', 'quickbook-FRN100000384-S103-1'),
(79, 'FRN100000384', 'quickbook-FRN100000384', '1033', '2', '', '', 4, 75.00, 262.50, 0.00, 262.50, '103', 'quickbook-FRN100000384-S103-2'),
(80, 'FRN100000384', 'quickbook-FRN100000384', '1033', '3', '', '', 2, 22.50, 45.00, 0.00, 45.00, '103', 'quickbook-FRN100000384-S103-3'),
(81, 'FRN100000384', 'quickbook-FRN100000384', '1021', '1', '', '', 15, 10.00, 150.00, 0.00, 150.00, '67', 'quickbook-FRN100000384-S67-1'),
(82, 'FRN100000384', 'quickbook-FRN100000384', '1021', '2', '', '', 1, 275.00, 275.00, 0.00, 275.00, '67', 'quickbook-FRN100000384-S67-2'),
(83, 'FRN100000384', 'quickbook-FRN100000384', '1032', '1', '', '', 20, 15.00, 300.00, 0.00, 300.00, '99', 'quickbook-FRN100000384-S99-1'),
(84, 'FRN100000384', 'quickbook-FRN100000384', '1032', '2', '', '', 7, 12.00, 84.00, 0.00, 84.00, '99', 'quickbook-FRN100000384-S99-2'),
(85, 'FRN100000384', 'quickbook-FRN100000384', '1030', '1', '', '', 2, 25.00, 50.00, 0.00, 50.00, '95', 'quickbook-FRN100000384-S95-1'),
(86, 'FRN100000384', 'quickbook-FRN100000384', '1030', '2', '', '', 1, 35.00, 35.00, 0.00, 35.00, '95', 'quickbook-FRN100000384-S95-2'),
(87, 'FRN100000384', 'quickbook-FRN100000384', '1030', '3', '', '', 15, 8.75, 131.25, 0.00, 131.25, '95', 'quickbook-FRN100000384-S95-3'),
(88, 'FRN100000384', 'quickbook-FRN100000384', '1029', '1', '', '', 5, 15.00, 75.00, 0.00, 75.00, '93', 'quickbook-FRN100000384-S93-1'),
(89, 'FRN100000384', 'quickbook-FRN100000384', '1029', '2', '', '', 6, 12.00, 72.00, 0.00, 72.00, '93', 'quickbook-FRN100000384-S93-2'),
(90, 'FRN100000384', 'quickbook-FRN100000384', '1029', '3', '', '', 1, 275.00, 275.00, 0.00, 275.00, '93', 'quickbook-FRN100000384-S93-3'),
(91, 'FRN100000384', 'quickbook-FRN100000384', '1022', '1', '', '', 3, 25.00, 75.00, 0.00, 75.00, '68', 'quickbook-FRN100000384-S68-1'),
(92, 'FRN100000384', 'quickbook-FRN100000384', '1005', '1', '', '', 2, 25.00, 50.00, 0.00, 50.00, '13', 'quickbook-FRN100000384-S13-1'),
(93, 'FRN100000384', 'quickbook-FRN100000384', '1006', '1', '', '', 4, 20.00, 80.00, 0.00, 80.00, '14', 'quickbook-FRN100000384-S14-1'),
(94, 'FRN100000384', 'quickbook-FRN100000384', '1028', '1', '', '', 3, 25.00, 75.00, 0.00, 75.00, '92', 'quickbook-FRN100000384-S92-1'),
(95, 'FRN100000384', 'quickbook-FRN100000384', '1027', '1', '', '', 2, 25.00, 50.00, 0.00, 50.00, '75', 'quickbook-FRN100000384-S75-1'),
(96, 'FRN100000384', 'quickbook-FRN100000384', '1027', '2', '', '', 1, 35.00, 35.00, 0.00, 35.00, '75', 'quickbook-FRN100000384-S75-2'),
(97, 'FRN100000384', 'quickbook-FRN100000384', '1025', '1', '', '', 4, 30.00, 120.00, 0.00, 120.00, '71', 'quickbook-FRN100000384-S71-1'),
(98, 'FRN100000384', 'quickbook-FRN100000384', '1025', '2', '', '', 1, 35.00, 35.00, 0.00, 35.00, '71', 'quickbook-FRN100000384-S71-2'),
(99, 'FRN100000384', 'quickbook-FRN100000384', '1025', '3', '', '', 1, 50.00, 50.00, 0.00, 50.00, '71', 'quickbook-FRN100000384-S71-3'),
(100, 'FRN100000384', 'quickbook-FRN100000384', '1024', '1', '', '', 12, 4.00, 48.00, 0.00, 48.00, '70', 'quickbook-FRN100000384-S70-1'),
(101, 'FRN100000384', 'quickbook-FRN100000384', '1024', '2', '', '', 15, 4.00, 60.00, 0.00, 60.00, '70', 'quickbook-FRN100000384-S70-2'),
(102, 'FRN100000384', 'quickbook-FRN100000384', '1024', '3', '', '', 4, 12.00, 48.00, 0.00, 48.00, '70', 'quickbook-FRN100000384-S70-3'),
(103, 'FRN100000384', 'quickbook-FRN100000384', '1023', '1', '', '', 2, 35.00, 70.00, 0.00, 70.00, '69', 'quickbook-FRN100000384-S69-1'),
(104, 'FRN100000384', 'quickbook-FRN100000384', '1019', '1', '', '', 4, 20.00, 80.00, 0.00, 80.00, '65', 'quickbook-FRN100000384-S65-1'),
(105, 'FRN100000384', 'quickbook-FRN100000384', '1018', '1', '', '', 4, 20.00, 80.00, 0.00, 80.00, '64', 'quickbook-FRN100000384-S64-1'),
(106, 'FRN100000384', 'quickbook-FRN100000384', '1016', '1', '', '', 3, 25.00, 75.00, 0.00, 75.00, '60', 'quickbook-FRN100000384-S60-1'),
(107, 'FRN100000384', 'quickbook-FRN100000384', '1015', '1', '', '', 4, 75.00, 300.00, 0.00, 300.00, '49', 'quickbook-FRN100000384-S49-1'),
(108, 'FRN100000384', 'quickbook-FRN100000384', '1015', '2', '', '', 5, 50.00, 250.00, 0.00, 250.00, '49', 'quickbook-FRN100000384-S49-2'),
(109, 'FRN100000384', 'quickbook-FRN100000384', '1015', '3', '', '', 1, 275.00, 275.00, 0.00, 275.00, '49', 'quickbook-FRN100000384-S49-3'),
(110, 'FRN100000384', 'quickbook-FRN100000384', '1015', '4', '', '', 8, 22.50, 180.00, 0.00, 180.00, '49', 'quickbook-FRN100000384-S49-4'),
(111, 'FRN100000384', 'quickbook-FRN100000384', '1009', '1', '', '', 0, 0.00, 103.55, 0.00, 103.55, '27', 'quickbook-FRN100000384-S27-1'),
(112, 'FRN100000384', 'quickbook-FRN100000384', '1010', '1', '', '', 5, 75.00, 375.00, 0.00, 375.00, '34', 'quickbook-FRN100000384-S34-1'),
(113, 'FRN100000384', 'quickbook-FRN100000384', '1007', '1', '', '', 10, 75.00, 750.00, 0.00, 750.00, '16', 'quickbook-FRN100000384-S16-1');

-- --------------------------------------------------------

--
-- Table structure for table `syncledger_transaction`
--

CREATE TABLE `syncledger_transaction` (
  `syncledgertransaction_id` int(11) NOT NULL,
  `frenns_id` varchar(255) NOT NULL,
  `unique_frenns_id` varchar(200) DEFAULT NULL,
  `company_account_number` varchar(255) DEFAULT NULL,
  `companyname` varchar(255) DEFAULT NULL,
  `companynumber` varchar(255) DEFAULT NULL,
  `collection_date` varchar(255) NOT NULL,
  `last_updated` varchar(255) NOT NULL,
  `entry_number` varchar(255) NOT NULL,
  `sourcetype` varchar(255) NOT NULL,
  `sourceid` varchar(100) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `entry_date` varchar(255) DEFAULT NULL,
  `debit_amount` int(11) DEFAULT NULL,
  `TransactionLastUpdatedOn` varchar(255) NOT NULL,
  `NominalAccountCode` varchar(255) DEFAULT NULL,
  `CreditAmount` int(11) DEFAULT NULL,
  `InvoiceDescription` text,
  `Reference` varchar(255) DEFAULT NULL,
  `updateId` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `syncledger_transaction`
--

INSERT INTO `syncledger_transaction` (`syncledgertransaction_id`, `frenns_id`, `unique_frenns_id`, `company_account_number`, `companyname`, `companynumber`, `collection_date`, `last_updated`, `entry_number`, `sourcetype`, `sourceid`, `description`, `entry_date`, `debit_amount`, `TransactionLastUpdatedOn`, `NominalAccountCode`, `CreditAmount`, `InvoiceDescription`, `Reference`, `updateId`) VALUES
(1, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Amy\'s Bird Sanctuary', NULL, '2017-12-20', '2017-05-01', '', 'Payment', '35', 'Amy claims the pest control did not occur', '2017-05-01', 105, '2017-05-01', '1234', 105, 'Amy claims the pest control did not occur', '', 'quickbook-FRN100000384'),
(2, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Amy\'s Bird Sanctuary', NULL, '2017-12-20', '2017-05-07', '', 'Payment', '', 'Created by QB Online to link credits to charges.', '2017-05-07', 0, '2017-05-07', '1234', 0, 'Created by QB Online to link credits to charges.', '', 'quickbook-FRN100000384'),
(3, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Amy\'s Bird Sanctuary', NULL, '2017-12-20', '2017-05-22', '', 'Payment', '4', '', '2017-05-22', 108, '2017-05-22', '1234', 108, '', '', 'quickbook-FRN100000384'),
(4, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Amy\'s Bird Sanctuary', NULL, '2017-12-20', '2017-05-23', '', 'Payment', '4', '', '2017-05-23', 220, '2017-05-23', '1234', 220, '', '', 'quickbook-FRN100000384'),
(5, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Bill\'s Windsurf Shop', NULL, '2017-12-20', '2017-02-27', '', 'Payment', '35', '', '2017-02-27', 175, '2017-02-27', '1234', 175, '', '', 'quickbook-FRN100000384'),
(6, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Cool Cars', NULL, '2017-12-20', '2017-05-17', '', 'Payment', '35', '', '2017-05-17', 694, '2017-05-17', '1234', 694, '', '', 'quickbook-FRN100000384'),
(7, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Cool Cars', NULL, '2017-12-20', '2017-05-24', '', 'Payment', '4', '', '2017-05-24', 1676, '2017-05-24', '1234', 1676, '', '', 'quickbook-FRN100000384'),
(8, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Diego Rodriguez', NULL, '2017-12-20', '2017-05-20', '', 'Sales Receipt', '4', '', '2017-05-20', 140, '2017-05-20', '1234', 140, '', '', 'quickbook-FRN100000384'),
(9, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Dukes Basketball Camp', NULL, '2017-12-20', '2017-04-27', '', 'Payment', '4', '', '2017-04-27', 460, '2017-04-27', '1234', 460, '', '', 'quickbook-FRN100000384'),
(10, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Dylan Sollfrank', NULL, '2017-12-20', '2017-05-19', '', 'Sales Receipt', '35', '', '2017-05-19', 338, '2017-05-19', '1234', 338, '', '', 'quickbook-FRN100000384'),
(11, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'John Melton', NULL, '2017-12-20', '2017-05-22', '', 'Payment', '4', '', '2017-05-22', 300, '2017-05-22', '1234', 300, '', '', 'quickbook-FRN100000384'),
(12, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Kate Whelan', NULL, '2017-12-20', '2017-04-29', '', 'Sales Receipt', '35', '', '2017-04-29', 225, '2017-04-29', '1234', 225, '', '', 'quickbook-FRN100000384'),
(13, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Pye\'s Cakes', NULL, '2017-12-20', '2017-05-22', '', 'Sales Receipt', '4', '', '2017-05-22', 79, '2017-05-22', '1234', 79, '', '', 'quickbook-FRN100000384'),
(14, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Pye\'s Cakes', NULL, '2017-12-20', '2017-05-22', '', 'Refund', '35', '', '2017-05-22', -88, '2017-05-22', '1234', -88, '', '', 'quickbook-FRN100000384'),
(15, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Sushi by Katsuyuki', NULL, '2017-12-20', '2017-05-20', '', 'Payment', '4', '', '2017-05-20', 80, '2017-05-20', '1234', 80, '', '', 'quickbook-FRN100000384'),
(16, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Travis Waldron', NULL, '2017-12-20', '2017-05-22', '', 'Payment', '35', '', '2017-05-22', 104, '2017-05-22', '1234', 104, '', '', 'quickbook-FRN100000384'),
(17, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Travis Waldron', NULL, '2017-12-20', '2017-05-23', '', 'Payment', '4', '', '2017-05-23', 81, '2017-05-23', '1234', 81, '', '', 'quickbook-FRN100000384'),
(18, 'FRN100000384', 'quickbook-FRN100000384', NULL, '', NULL, '2017-12-20', '2017-01-08', '', 'Deposit', '35', 'Opening Balance', '2017-01-08', 5000, '2017-01-08', '1234', 5000, 'Opening Balance', '', 'quickbook-FRN100000384'),
(19, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Robertson & Associates', NULL, '2017-12-20', '2017-01-20', '', 'Bill', '33', 'Lawyer fees related to Startup', '2017-01-20', 300, '2017-01-20', '1234', 300, 'Lawyer fees related to Startup', '', 'quickbook-FRN100000384'),
(20, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Robertson & Associates', NULL, '2017-12-20', '2017-02-19', '', 'Bill Payment (Check)', '35', '', '2017-02-19', -300, '2017-02-19', '1234', -300, '', '', 'quickbook-FRN100000384'),
(21, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Robertson & Associates', NULL, '2017-12-20', '2017-03-13', '', 'Expense', '35', '', '2017-03-13', -250, '2017-03-13', '1234', -250, '', '', 'quickbook-FRN100000384'),
(22, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Tania\'s Nursery', NULL, '2017-12-20', '2017-03-20', '', 'Expense', '41', '', '2017-03-20', 158, '2017-03-20', '1234', 158, '', '', 'quickbook-FRN100000384'),
(23, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Chin\'s Gas and Oil', NULL, '2017-12-20', '2017-04-04', '', 'Check', '35', '', '2017-04-04', -55, '2017-04-04', '1234', -55, '', '', 'quickbook-FRN100000384'),
(24, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Cal Telephone', NULL, '2017-12-20', '2017-04-08', '', 'Bill', '33', '', '2017-04-08', 57, '2017-04-08', '1234', 57, '', '', 'quickbook-FRN100000384'),
(25, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'PG&E', NULL, '2017-12-20', '2017-04-09', '', 'Bill', '33', '', '2017-04-09', 86, '2017-04-09', '1234', 86, '', '', 'quickbook-FRN100000384'),
(26, 'FRN100000384', 'quickbook-FRN100000384', NULL, '', NULL, '2017-12-20', '2017-04-10', '', 'Sales Tax Payment', '35', 'Q1 Payment', '2017-04-10', 38, '2017-04-10', '1234', 38, 'Q1 Payment', '', 'quickbook-FRN100000384'),
(27, 'FRN100000384', 'quickbook-FRN100000384', NULL, '', NULL, '2017-12-20', '2017-04-10', '', 'Sales Tax Payment', '35', 'Q1 Payment', '2017-04-10', 39, '2017-04-10', '1234', 39, 'Q1 Payment', '', 'quickbook-FRN100000384'),
(28, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Tania\'s Nursery', NULL, '2017-12-20', '2017-04-13', '', 'Expense', '35', '', '2017-04-13', -89, '2017-04-13', '1234', -89, '', '', 'quickbook-FRN100000384'),
(29, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Books by Bessie', NULL, '2017-12-20', '2017-04-13', '', 'Check', '35', '', '2017-04-13', -55, '2017-04-13', '1234', -55, '', '', 'quickbook-FRN100000384'),
(30, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Hicks Hardware', NULL, '2017-12-20', '2017-04-17', '', 'Bill', '33', 'Opening Balance', '2017-04-17', 250, '2017-04-17', '1234', 250, 'Opening Balance', '', 'quickbook-FRN100000384'),
(31, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Chin\'s Gas and Oil', NULL, '2017-12-20', '2017-04-19', '', 'Check', '35', '', '2017-04-19', -62, '2017-04-19', '1234', -62, '', '', 'quickbook-FRN100000384'),
(32, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Tania\'s Nursery', NULL, '2017-12-20', '2017-04-20', '', 'Expense', '35', '', '2017-04-20', -108, '2017-04-20', '1234', -108, '', '', 'quickbook-FRN100000384'),
(33, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Hicks Hardware', NULL, '2017-12-20', '2017-05-03', '', 'Bill Payment (Check)', '35', '', '2017-05-03', -250, '2017-05-03', '1234', -250, '', '', 'quickbook-FRN100000384'),
(34, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Hicks Hardware', NULL, '2017-12-20', '2017-05-06', '', 'Expense', '35', '', '2017-05-06', -24, '2017-05-06', '1234', -24, '', '', 'quickbook-FRN100000384'),
(35, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Cal Telephone', NULL, '2017-12-20', '2017-05-08', '', 'Bill', '33', '', '2017-05-08', 74, '2017-05-08', '1234', 74, '', '', 'quickbook-FRN100000384'),
(36, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Hall Properties', NULL, '2017-12-20', '2017-05-08', '', 'Bill', '33', '', '2017-05-08', 900, '2017-05-08', '1234', 900, '', '', 'quickbook-FRN100000384'),
(37, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'PG&E', NULL, '2017-12-20', '2017-05-08', '', 'Bill', '33', '', '2017-05-08', 114, '2017-05-08', '1234', 114, '', '', 'quickbook-FRN100000384'),
(38, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Tim Philip Masonry', NULL, '2017-12-20', '2017-05-09', '', 'Bill', '33', 'Opening Balance', '2017-05-09', 666, '2017-05-09', '1234', 666, 'Opening Balance', '', 'quickbook-FRN100000384'),
(39, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Chin\'s Gas and Oil', NULL, '2017-12-20', '2017-05-09', '', 'Expense', '41', '', '2017-05-09', 65, '2017-05-09', '1234', 65, '', '', 'quickbook-FRN100000384'),
(40, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Books by Bessie', NULL, '2017-12-20', '2017-05-10', '', 'Bill', '33', '', '2017-05-10', 75, '2017-05-10', '1234', 75, '', '', 'quickbook-FRN100000384'),
(41, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Hicks Hardware', NULL, '2017-12-20', '2017-05-12', '', 'Expense', '41', '', '2017-05-12', 88, '2017-05-12', '1234', 88, '', '', 'quickbook-FRN100000384'),
(42, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Tania\'s Nursery', NULL, '2017-12-20', '2017-05-14', '', 'Expense', '41', '', '2017-05-14', 55, '2017-05-14', '1234', 55, '', '', 'quickbook-FRN100000384'),
(43, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Tony Rondonuwu', NULL, '2017-12-20', '2017-05-15', '', 'Check', '35', '', '2017-05-15', -100, '2017-05-15', '1234', -100, '', '', 'quickbook-FRN100000384'),
(44, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Bob\'s Burger Joint', NULL, '2017-12-20', '2017-05-15', '', 'Cash Expense', '35', '', '2017-05-15', -6, '2017-05-15', '1234', -6, '', '', 'quickbook-FRN100000384'),
(45, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Squeaky Kleen Car Wash', NULL, '2017-12-20', '2017-05-15', '', 'Cash Expense', '35', '', '2017-05-15', -20, '2017-05-15', '1234', -20, '', '', 'quickbook-FRN100000384'),
(46, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Chin\'s Gas and Oil', NULL, '2017-12-20', '2017-05-16', '', 'Cash Expense', '35', '', '2017-05-16', -52, '2017-05-16', '1234', -52, '', '', 'quickbook-FRN100000384'),
(47, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Chin\'s Gas and Oil', NULL, '2017-12-20', '2017-05-16', '', 'Check', '35', '', '2017-05-16', -185, '2017-05-16', '1234', -185, '', '', 'quickbook-FRN100000384'),
(48, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Brosnahan Insurance Agency', NULL, '2017-12-20', '2017-05-17', '', 'Bill', '33', 'Opening Balance', '2017-05-17', 2000, '2017-05-17', '1234', 2000, 'Opening Balance', '', 'quickbook-FRN100000384'),
(49, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Brosnahan Insurance Agency', NULL, '2017-12-20', '2017-05-17', '', 'Bill', '33', '', '2017-05-17', 241, '2017-05-17', '1234', 241, '', '', 'quickbook-FRN100000384'),
(50, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Hall Properties', NULL, '2017-12-20', '2017-05-17', '', 'Bill Payment (Check)', '35', '', '2017-05-17', -900, '2017-05-17', '1234', -900, '', '', 'quickbook-FRN100000384'),
(51, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Mahoney Mugs', NULL, '2017-12-20', '2017-05-18', '', 'Check', '35', '', '2017-05-18', -18, '2017-05-18', '1234', -18, '', '', 'quickbook-FRN100000384'),
(52, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Tania\'s Nursery', NULL, '2017-12-20', '2017-05-18', '', 'Expense', '41', '', '2017-05-18', 82, '2017-05-18', '1234', 82, '', '', 'quickbook-FRN100000384'),
(53, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Hicks Hardware', NULL, '2017-12-20', '2017-05-18', '', 'Expense', '35', '', '2017-05-18', -216, '2017-05-18', '1234', -216, '', '', 'quickbook-FRN100000384'),
(54, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Bob\'s Burger Joint', NULL, '2017-12-20', '2017-05-20', '', 'Cash Expense', '35', '', '2017-05-20', -4, '2017-05-20', '1234', -4, '', '', 'quickbook-FRN100000384'),
(55, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Brosnahan Insurance Agency', NULL, '2017-12-20', '2017-05-21', '', 'Bill Payment (Check)', '35', '', '2017-05-21', -2000, '2017-05-21', '1234', -2000, '', '', 'quickbook-FRN100000384'),
(56, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Norton Lumber and Building Materials', NULL, '2017-12-20', '2017-05-21', '', 'Bill', '33', '', '2017-05-21', 104, '2017-05-21', '1234', 104, '', '', 'quickbook-FRN100000384'),
(57, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Books by Bessie', NULL, '2017-12-20', '2017-05-22', '', 'Bill Payment (Check)', '35', '', '2017-05-22', -75, '2017-05-22', '1234', -75, '', '', 'quickbook-FRN100000384'),
(58, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Diego\'s Road Warrior Bodyshop', NULL, '2017-12-20', '2017-05-22', '', 'Bill', '33', '', '2017-05-22', 755, '2017-05-22', '1234', 755, '', '', 'quickbook-FRN100000384'),
(59, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Tim Philip Masonry', NULL, '2017-12-20', '2017-05-22', '', 'Purchase Order', '33', '', '2017-05-22', 125, '2017-05-22', '1234', 125, '', '', 'quickbook-FRN100000384'),
(60, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Cal Telephone', NULL, '2017-12-20', '2017-05-22', '', 'Bill Payment (Credit Card)', '41', '', '2017-05-22', 74, '2017-05-22', '1234', 74, '', '', 'quickbook-FRN100000384'),
(61, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Ellis Equipment Rental', NULL, '2017-12-20', '2017-05-22', '', 'Expense', '41', '', '2017-05-22', 112, '2017-05-22', '1234', 112, '', '', 'quickbook-FRN100000384'),
(62, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Lee Advertising', NULL, '2017-12-20', '2017-05-22', '', 'Expense', '41', '', '2017-05-22', 75, '2017-05-22', '1234', 75, '', '', 'quickbook-FRN100000384'),
(63, 'FRN100000384', 'quickbook-FRN100000384', NULL, '', NULL, '2017-12-20', '2017-05-22', '', 'Deposit', '35', '', '2017-05-22', 219, '2017-05-22', '1234', 219, '', '', 'quickbook-FRN100000384'),
(64, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Squeaky Kleen Car Wash', NULL, '2017-12-20', '2017-05-22', '', 'Check', '35', '', '2017-05-22', -20, '2017-05-22', '1234', -20, '', '', 'quickbook-FRN100000384'),
(65, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'PG&E', NULL, '2017-12-20', '2017-05-23', '', 'Bill Payment (Check)', '35', '', '2017-05-23', -114, '2017-05-23', '1234', -114, '', '', 'quickbook-FRN100000384'),
(66, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Tim Philip Masonry', NULL, '2017-12-20', '2017-05-23', '', 'Bill Payment (Check)', '35', '', '2017-05-23', -666, '2017-05-23', '1234', -666, '', '', 'quickbook-FRN100000384'),
(67, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Tania\'s Nursery', NULL, '2017-12-20', '2017-05-23', '', 'Expense', '35', '', '2017-05-23', -47, '2017-05-23', '1234', -47, '', '', 'quickbook-FRN100000384'),
(68, 'FRN100000384', 'quickbook-FRN100000384', NULL, '', NULL, '2017-12-20', '2017-05-23', '', 'Deposit', '35', '', '2017-05-23', 408, '2017-05-23', '1234', 408, '', '', 'quickbook-FRN100000384'),
(69, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Chin\'s Gas and Oil', NULL, '2017-12-20', '2017-05-23', '', 'Cash Expense', '35', '', '2017-05-23', -63, '2017-05-23', '1234', -63, '', '', 'quickbook-FRN100000384'),
(70, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Robertson & Associates', NULL, '2017-12-20', '2017-05-24', '', 'Bill', '33', '', '2017-05-24', 315, '2017-05-24', '1234', 315, '', '', 'quickbook-FRN100000384'),
(71, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Hicks Hardware', NULL, '2017-12-20', '2017-05-24', '', 'Purchase Order', '33', '', '2017-05-24', 229, '2017-05-24', '1234', 229, '', '', 'quickbook-FRN100000384'),
(72, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Hicks Hardware', NULL, '2017-12-20', '2017-05-24', '', 'Check', '35', '', '2017-05-24', -229, '2017-05-24', '1234', -229, '', '', 'quickbook-FRN100000384'),
(73, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Cal Telephone', NULL, '2017-12-20', '2017-05-24', '', 'Bill Payment (Credit Card)', '41', '', '2017-05-24', 57, '2017-05-24', '1234', 57, '', '', 'quickbook-FRN100000384'),
(74, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Norton Lumber and Building Materials', NULL, '2017-12-20', '2017-05-24', '', 'Bill Payment (Credit Card)', '41', '', '2017-05-24', 104, '2017-05-24', '1234', 104, '', '', 'quickbook-FRN100000384'),
(75, 'FRN100000384', 'quickbook-FRN100000384', NULL, '', NULL, '2017-12-20', '2017-05-24', '', 'Deposit', '35', '', '2017-05-24', 868, '2017-05-24', '1234', 868, '', '', 'quickbook-FRN100000384'),
(76, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Chin\'s Gas and Oil', NULL, '2017-12-20', '2017-05-24', '', 'Expense', '41', '', '2017-05-24', 53, '2017-05-24', '1234', 53, '', '', 'quickbook-FRN100000384'),
(77, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Norton Lumber and Building Materials', NULL, '2017-12-20', '2017-05-24', '', 'Purchase Order', '33', '', '2017-05-24', 205, '2017-05-24', '1234', 205, '', '', 'quickbook-FRN100000384'),
(78, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Norton Lumber and Building Materials', NULL, '2017-12-20', '2017-05-24', '', 'Bill', '33', '', '2017-05-24', 205, '2017-05-24', '1234', 205, '', '', 'quickbook-FRN100000384'),
(79, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Pam Seitz', NULL, '2017-12-20', '2017-05-24', '', 'Expense', '35', '', '2017-05-24', -75, '2017-05-24', '1234', -75, '', '', 'quickbook-FRN100000384'),
(80, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Tania\'s Nursery', NULL, '2017-12-20', '2017-05-27', '', 'Cash Expense', '35', '', '2017-05-27', -24, '2017-05-27', '1234', -24, '', '', 'quickbook-FRN100000384'),
(81, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Bob\'s Burger Joint', NULL, '2017-12-20', '2017-05-29', '', 'Credit Card Expense', '41', 'Bought lunch for crew 102', '2017-05-29', 19, '2017-05-29', '1234', 19, 'Bought lunch for crew 102', '', 'quickbook-FRN100000384'),
(82, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Squeaky Kleen Car Wash', NULL, '2017-12-20', '2017-05-29', '', 'Credit Card Expense', '41', '', '2017-05-29', 20, '2017-05-29', '1234', 20, '', '', 'quickbook-FRN100000384'),
(83, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Hicks Hardware', NULL, '2017-12-20', '2017-05-30', '', 'Credit Card Expense', '41', '', '2017-05-30', 42, '2017-05-30', '1234', 42, '', '', 'quickbook-FRN100000384'),
(84, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Squeaky Kleen Car Wash', NULL, '2017-12-20', '2017-06-05', '', 'Credit Card Expense', '41', '', '2017-06-05', 20, '2017-06-05', '1234', 20, '', '', 'quickbook-FRN100000384'),
(85, 'FRN100000384', 'quickbook-FRN100000384', NULL, '', NULL, '2017-12-20', '2017-06-07', '', 'Credit Card Credit', '41', 'Monthly Payment', '2017-06-07', -900, '2017-06-07', '1234', -900, 'Monthly Payment', '', 'quickbook-FRN100000384'),
(86, 'FRN100000384', 'quickbook-FRN100000384', NULL, '', NULL, '2017-12-20', '2017-06-18', '', 'Credit Card Expense', '41', '', '2017-06-18', 34, '2017-06-18', '1234', 34, '', '', 'quickbook-FRN100000384');

-- --------------------------------------------------------

--
-- Table structure for table `syncnominal`
--

CREATE TABLE `syncnominal` (
  `syncnominal_id` int(11) NOT NULL,
  `frenns_id` varchar(255) NOT NULL,
  `unique_frenns_id` varchar(200) DEFAULT NULL,
  `company_account_number` varchar(255) DEFAULT NULL,
  `companyname` varchar(255) DEFAULT NULL,
  `companynumber` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `account` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `account_type` varchar(255) DEFAULT NULL,
  `total_debit` int(11) NOT NULL,
  `total_credit` int(11) DEFAULT NULL,
  `collection_date` varchar(255) DEFAULT NULL,
  `last_updated` varchar(255) DEFAULT NULL,
  `updateId` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `syncnominal`
--

INSERT INTO `syncnominal` (`syncnominal_id`, `frenns_id`, `unique_frenns_id`, `company_account_number`, `companyname`, `companynumber`, `type`, `account`, `name`, `account_type`, `total_debit`, `total_credit`, `collection_date`, `last_updated`, `updateId`) VALUES
(1, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Amy\'s Bird Sanctuary', NULL, '9876', NULL, '', 'Invoice', 205, 205, '2017-12-20', '2017-04-06', 'quickbook-FRN100000384'),
(2, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Amy\'s Bird Sanctuary', NULL, '9876', NULL, '', 'Invoice', 459, 459, '2017-12-20', '2017-05-02', 'quickbook-FRN100000384'),
(3, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Amy\'s Bird Sanctuary', NULL, '9876', NULL, '', 'Credit Memo', -100, -100, '2017-12-20', '2017-05-07', 'quickbook-FRN100000384'),
(4, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Amy\'s Bird Sanctuary', NULL, '9876', NULL, '', 'Invoice', 216, 216, '2017-12-20', '2017-05-21', 'quickbook-FRN100000384'),
(5, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Amy\'s Bird Sanctuary', NULL, '9876', NULL, '', 'Time Charge', 375, 375, '2017-12-20', '2017-05-21', 'quickbook-FRN100000384'),
(6, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Amy\'s Bird Sanctuary', NULL, '9876', NULL, '', 'Invoice', 2775, 2775, '2017-12-20', '2017-06-28', 'quickbook-FRN100000384'),
(7, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Amy\'s Bird Sanctuary', NULL, '9876', NULL, '', 'Invoice', 1000, 1000, '2017-12-20', '2017-06-30', 'quickbook-FRN100000384'),
(8, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Amy\'s Bird Sanctuary', NULL, '9876', NULL, '', 'Invoice', 700, 700, '2017-12-20', '2017-06-30', 'quickbook-FRN100000384'),
(9, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Amy\'s Bird Sanctuary', NULL, '9876', NULL, '', 'Invoice', 700, 700, '2017-12-20', '2017-06-30', 'quickbook-FRN100000384'),
(10, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Amy\'s Bird Sanctuary', NULL, '9876', NULL, '', 'Invoice', 1500, 1500, '2017-12-20', '2017-06-30', 'quickbook-FRN100000384'),
(11, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Amy\'s Bird Sanctuary', NULL, '9876', NULL, '', 'Invoice', 182, 182, '2017-12-20', '2017-06-30', 'quickbook-FRN100000384'),
(12, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Bill\'s Windsurf Shop', NULL, '9876', NULL, '', 'Invoice', 85, 85, '2017-12-20', '2017-04-06', 'quickbook-FRN100000384'),
(13, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Bill\'s Windsurf Shop', NULL, '9876', NULL, '', 'Invoice', 200, 200, '2017-12-20', '2017-06-30', 'quickbook-FRN100000384'),
(14, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Bill\'s Windsurf Shop', NULL, '9876', NULL, '', 'Invoice', 80, 80, '2017-12-20', '2017-06-30', 'quickbook-FRN100000384'),
(15, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Cool Cars', NULL, '9876', NULL, '', 'Invoice', 2370, 2370, '2017-12-20', '2017-05-12', 'quickbook-FRN100000384'),
(16, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Cool Cars', NULL, '9876', NULL, '', 'Invoice', 200, 200, '2017-12-20', '2017-06-30', 'quickbook-FRN100000384'),
(17, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Cool Cars', NULL, '9876', NULL, '', 'Invoice', 10000, 10000, '2017-12-20', '2017-06-30', 'quickbook-FRN100000384'),
(18, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Diego Rodriguez', NULL, '9876', NULL, '', 'Invoice', 550, 550, '2017-12-20', '2017-02-06', 'quickbook-FRN100000384'),
(19, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Dukes Basketball Camp', NULL, '9876', NULL, '', 'Invoice', 460, 460, '2017-12-20', '2017-04-06', 'quickbook-FRN100000384'),
(20, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Dylan Sollfrank', NULL, '9876', NULL, '', 'Invoice', 2300, 2300, '2017-12-20', '2017-06-30', 'quickbook-FRN100000384'),
(21, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Geeta Kalapatapu', NULL, '9876', NULL, '', 'Estimate', 583, 583, '2017-12-20', '2017-05-12', 'quickbook-FRN100000384'),
(22, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Geeta Kalapatapu', NULL, '9876', NULL, '', 'Invoice', 629, 629, '2017-12-20', '2017-05-23', 'quickbook-FRN100000384'),
(23, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Jeff\'s Jalopies', NULL, '9876', NULL, '', 'Invoice', 81, 81, '2017-12-20', '2017-05-02', 'quickbook-FRN100000384'),
(24, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'John Melton', NULL, '9876', NULL, '', 'Invoice', 750, 750, '2017-12-20', '2017-04-29', 'quickbook-FRN100000384'),
(25, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Kookies by Kathy', NULL, '9876', NULL, '', 'Invoice', 75, 75, '2017-12-20', '2017-04-05', 'quickbook-FRN100000384'),
(26, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Mark Cho', NULL, '9876', NULL, '', 'Invoice', 972, 972, '2017-12-20', '2017-05-24', 'quickbook-FRN100000384'),
(27, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Paulsen Medical Supplies', NULL, '9876', NULL, '', 'Estimate', 1005, 1005, '2017-12-20', '2017-05-20', 'quickbook-FRN100000384'),
(28, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Paulsen Medical Supplies', NULL, '9876', NULL, '', 'Invoice', 955, 955, '2017-12-20', '2017-05-22', 'quickbook-FRN100000384'),
(29, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Red Rock Diner', NULL, '9876', NULL, '', 'Invoice', 156, 156, '2017-12-20', '2017-03-16', 'quickbook-FRN100000384'),
(30, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Red Rock Diner', NULL, '9876', NULL, '', 'Estimate', 70, 70, '2017-12-20', '2017-05-20', 'quickbook-FRN100000384'),
(31, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Red Rock Diner', NULL, '9876', NULL, '', 'Invoice', 70, 70, '2017-12-20', '2017-05-22', 'quickbook-FRN100000384'),
(32, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Rondonuwu Fruit and Vegi', NULL, '9876', NULL, '', 'Time Charge', 30, 30, '2017-12-20', '2017-05-22', 'quickbook-FRN100000384'),
(33, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Rondonuwu Fruit and Vegi', NULL, '9876', NULL, '', 'Time Charge', 45, 45, '2017-12-20', '2017-05-22', 'quickbook-FRN100000384'),
(34, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Rondonuwu Fruit and Vegi', NULL, '9876', NULL, '', 'Invoice', 924, 924, '2017-12-20', '2017-05-23', 'quickbook-FRN100000384'),
(35, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Sonnenschein Family Store', NULL, '9876', NULL, '', 'Estimate', 362, 362, '2017-12-20', '2017-05-21', 'quickbook-FRN100000384'),
(36, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Sonnenschein Family Store', NULL, '9876', NULL, '', 'Invoice', 428, 428, '2017-12-20', '2017-05-24', 'quickbook-FRN100000384'),
(37, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Sushi by Katsuyuki', NULL, '9876', NULL, '', 'Invoice', 400, 400, '2017-12-20', '2017-05-08', 'quickbook-FRN100000384'),
(38, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Sushi by Katsuyuki', NULL, '9876', NULL, '', 'Invoice', 80, 80, '2017-12-20', '2017-05-15', 'quickbook-FRN100000384'),
(39, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Sushi by Katsuyuki', NULL, '9876', NULL, '', 'Invoice', 80, 80, '2017-12-20', '2017-05-22', 'quickbook-FRN100000384'),
(40, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Travis Waldron', NULL, '9876', NULL, '', 'Invoice', 81, 81, '2017-12-20', '2017-05-12', 'quickbook-FRN100000384'),
(41, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Travis Waldron', NULL, '9876', NULL, '', 'Billable Expense Charge', 104, 104, '2017-12-20', '2017-05-21', 'quickbook-FRN100000384'),
(42, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Travis Waldron', NULL, '9876', NULL, '', 'Invoice', 104, 104, '2017-12-20', '2017-05-21', 'quickbook-FRN100000384'),
(43, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Travis Waldron', NULL, '9876', NULL, '', 'Charge', 75, 75, '2017-12-20', '2017-05-21', 'quickbook-FRN100000384'),
(44, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Travis Waldron', NULL, '9876', NULL, '', 'Invoice', 415, 415, '2017-12-20', '2017-05-21', 'quickbook-FRN100000384'),
(45, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Video Games by Dan', NULL, '9876', NULL, '', 'Charge', 300, 300, '2017-12-20', '2017-05-21', 'quickbook-FRN100000384'),
(46, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Video Games by Dan', NULL, '9876', NULL, '', 'Charge', 75, 75, '2017-12-20', '2017-05-21', 'quickbook-FRN100000384'),
(47, 'FRN100000384', 'quickbook-FRN100000384', NULL, 'Weiskopf Consulting', NULL, '9876', NULL, '', 'Invoice', 375, 375, '2017-12-20', '2017-05-22', 'quickbook-FRN100000384'),
(48, 'FRN100000384', 'quickbook-FRN100000384', NULL, '', NULL, '5678', NULL, '', 'Deposit', 600, 600, '2017-12-20', '2017-05-20', 'quickbook-FRN100000384');

-- --------------------------------------------------------

--
-- Table structure for table `syncreport_pl`
--

CREATE TABLE `syncreport_pl` (
  `syncreport_pl_id` int(11) NOT NULL,
  `frenns_id` varchar(255) NOT NULL,
  `unique_frenns_id` varchar(200) DEFAULT NULL,
  `pl_data` text,
  `updateId` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `syncreport_pl`
--

INSERT INTO `syncreport_pl` (`syncreport_pl_id`, `frenns_id`, `unique_frenns_id`, `pl_data`, `updateId`) VALUES
(1, 'FRN100000390', 'exactNL-FRN100000390', '[{\"PreviousYear\":2016,\"CurrentPeriod\":12,\"PreviousYearPeriod\":12,\"CurrencyCode\":\"EUR\",\"ResultCurrentYear\":61,\"ResultPreviousYear\":-7708.38,\"RevenueCurrentYear\":61,\"RevenuePreviousYear\":0,\"CostsCurrentYear\":0,\"CostsPreviousYear\":7708.38,\"ResultCurrentPeriod\":0,\"ResultPreviousYearPeriod\":0,\"RevenueCurrentPeriod\":0,\"RevenuePreviousYearPeriod\":0,\"CostsCurrentPeriod\":0,\"CostsPreviousYearPeriod\":0}]', NULL),
(2, 'FRN100000388', 'kashflow-FRN100000388', '{\"GetProfitAndLossResult\":{\"StartDate\":\"2016-01-01T00:00:00\",\"EndDate\":\"2017-12-27T00:00:00\",\"Turnover\":{\"anyType\":{\"enc_type\":0,\"enc_value\":{\"id\":23615192,\"Code\":100,\"Name\":\"Sale of goods\",\"debit\":\"0\",\"credit\":\"0\",\"balance\":\"1315.0000\"},\"enc_stype\":\"NominalCode\",\"enc_ns\":\"KashFlow\"}},\"TurnoverTotal\":\"1315.0000\",\"CostOfSales\":{\"anyType\":{\"enc_type\":0,\"enc_value\":{\"id\":23615195,\"Code\":2700,\"Name\":\"Materials purchased\",\"debit\":\"0\",\"credit\":\"0\",\"balance\":\"270.0000\"},\"enc_stype\":\"NominalCode\",\"enc_ns\":\"KashFlow\"}},\"CostOfSalesTotal\":\"270.0000\",\"GrossProfit\":\"1045.0000\",\"Expenses\":{\"anyType\":[{\"enc_type\":0,\"enc_value\":{\"id\":23615206,\"Code\":23302,\"Name\":\"Gas\",\"debit\":\"0\",\"credit\":\"0\",\"balance\":\"210.0000\"},\"enc_stype\":\"NominalCode\",\"enc_ns\":\"KashFlow\"},{\"enc_type\":0,\"enc_value\":{\"id\":23615226,\"Code\":26705,\"Name\":\"Other costs\",\"debit\":\"0\",\"credit\":\"0\",\"balance\":\"45.0000\"},\"enc_stype\":\"NominalCode\",\"enc_ns\":\"KashFlow\"}]},\"ExpensesTotal\":\"255.0000\",\"NetProfit\":\"790.0000\"},\"Status\":\"OK\",\"StatusDetail\":\"\"}', NULL),
(3, 'FRN100000393', 'reeleezee-FRN100000393', '{\"value\":[]}', NULL),
(4, 'FRN100000395', 'freeagent-FRN100000395', '{\"from\":\"2018-01-01\",\"to\":\"2018-01-02\",\"income\":\"0\",\"expenses\":\"1140\",\"operating_profit\":\"-1140\",\"less\":[{\"title\":\"Drawings\",\"total\":\"0\"},{\"title\":\"Adjustments\",\"total\":\"0\"}],\"retained_profit\":\"-1140\",\"retained_profit_brought_forward\":\"0\",\"retained_profit_carried_forward\":\"-1140\"}', NULL),
(5, 'FRN100000396', 'freeagent-FRN100000396', '{\"from\":\"2018-01-01\",\"to\":\"2018-01-02\",\"income\":\"6340\",\"expenses\":\"0\",\"operating_profit\":\"6340\",\"less\":[{\"title\":\"Corp. Tax\",\"total\":\"1205\"},{\"title\":\"Dividends\",\"total\":\"0\"},{\"title\":\"Adjustments\",\"total\":\"0\"}],\"retained_profit\":\"5135\",\"retained_profit_brought_forward\":\"660\",\"retained_profit_carried_forward\":\"5795\"}', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `syncsupplier`
--

CREATE TABLE `syncsupplier` (
  `syncsupplier_id` int(11) NOT NULL,
  `frenns_id` varchar(255) NOT NULL,
  `unique_frenns_id` varchar(200) DEFAULT NULL,
  `company_account_number` varchar(255) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `company_number` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `cust_supp_company` varchar(255) DEFAULT NULL,
  `custsupp_companynumber` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `postcode` varchar(255) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `vat_registration` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `collection_date` date DEFAULT NULL,
  `last_update` varchar(255) DEFAULT NULL,
  `contactId` varchar(255) DEFAULT NULL,
  `updateId` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `syncsupplier`
--

INSERT INTO `syncsupplier` (`syncsupplier_id`, `frenns_id`, `unique_frenns_id`, `company_account_number`, `company_name`, `company_number`, `type`, `cust_supp_company`, `custsupp_companynumber`, `account_number`, `name`, `address`, `city`, `postcode`, `country`, `vat_registration`, `contact_person`, `phone_number`, `email`, `collection_date`, `last_update`, `contactId`, `updateId`) VALUES
(1, 'FRN100000384', 'quickbook-FRN100000384', '', 'Bob\'s Burger Joint666666666', '', 'Supplier', '', '', '545454545454', 'Bob\'s Burger Joint', '', '', '', '', NULL, 'Bob\'s Burger Joint', '42343423', '', '2018-01-03', '2017-11-29T01:08:00-08:00', '56', 'quickbook-FRN100000384-56'),
(2, 'FRN100000384', 'quickbook-FRN100000384', '', 'Books by Bessie', '', 'Supplier', '', '', '1345', 'Books by Bessie', '15 Main St.', 'Palo Alto', '94303', 'CA', NULL, 'Books by Bessie', '(650) 555-7745', 'Books@Intuit.com', '2018-01-03', '2017-05-22T11:13:46-07:00', '30', 'quickbook-FRN100000384-30'),
(3, 'FRN100000384', 'quickbook-FRN100000384', '', 'Brosnahan Insurance Agency', '', 'Supplier', '', '', '7653412', 'Brosnahan Insurance Agency', 'P.O. Box 5', 'Middlefield', '94482', 'CA', NULL, 'Brosnahan Insurance Agency', '(650) 555-9912', '', '2018-01-03', '2017-05-21T15:28:48-07:00', '31', 'quickbook-FRN100000384-31'),
(4, 'FRN100000384', 'quickbook-FRN100000384', '', 'Cal Telephone123', '', 'Supplier', '', '', '', 'Cal Telephone', '10 Main St.', 'Palo Alto', '94303', 'CA', NULL, 'Cal Telephone', '(650) 555-1234', '', '2018-01-03', '2017-07-27T04:23:30-07:00', '32', 'quickbook-FRN100000384-32'),
(5, 'FRN100000384', 'quickbook-FRN100000384', '', 'Chin\'s Gas and Oil', '', 'Supplier', '', '', '', 'Chin\'s Gas and Oil', '', '', '', '', NULL, 'Chin\'s Gas and Oil', '', '', '2018-01-03', '2017-05-17T10:13:52-07:00', '33', 'quickbook-FRN100000384-33'),
(6, 'FRN100000384', 'quickbook-FRN100000384', '', 'Cigna Health Care', '', 'Supplier', '', '', '123456789', 'Cigna Health Care', '', '', '', '', NULL, 'Cigna Health Care', '(520) 555-9874', '', '2018-01-03', '2017-05-17T10:14:48-07:00', '34', 'quickbook-FRN100000384-34'),
(7, 'FRN100000384', 'quickbook-FRN100000384', '', 'Computers by Jenni', '', 'Supplier', '', '', '', 'Computers by Jenni', '1515 Main St.', 'Middlefield', '94482', 'CA', NULL, 'Computers by Jenni', '(650) 555-8721', 'Msfixit@Intuit.com', '2018-01-03', '2017-05-17T10:16:29-07:00', '35', 'quickbook-FRN100000384-35'),
(8, 'FRN100000384', 'quickbook-FRN100000384', '', 'Diego\'s Road Warrior Bodyshop', '', 'Supplier', '', '', '', 'Diego\'s Road Warrior Bodyshop', '', '', '', '', NULL, 'Diego\'s Road Warrior Bodyshop', '', '', '2018-01-03', '2017-05-22T11:31:42-07:00', '36', 'quickbook-FRN100000384-36'),
(9, 'FRN100000384', 'quickbook-FRN100000384', '', 'EDD', '', 'Supplier', '', '', '', 'EDD', '', '', '', '', NULL, 'EDD', '', '', '2018-01-03', '2017-05-17T10:17:10-07:00', '37', 'quickbook-FRN100000384-37'),
(10, 'FRN100000384', 'quickbook-FRN100000384', '', 'Ellis Equipment Rental', '', 'Supplier', '', '', '45555454545', 'Ellis Equipment Rental', '45896 Main St.', 'Middlefield', '94303', 'CA', NULL, 'Ellis Equipment Rental', '(650) 555-3333', 'Rental@intuit.com', '2018-01-03', '2017-11-22T00:34:29-08:00', '38', 'quickbook-FRN100000384-38'),
(11, 'FRN100000384', 'quickbook-FRN100000384', '', 'Fidelity', '', 'Supplier', '', '', '', 'Fidelity', '', '', '', '', NULL, 'Fidelity', '', '', '2018-01-03', '2017-05-17T10:20:03-07:00', '39', 'quickbook-FRN100000384-39'),
(12, 'FRN100000384', 'quickbook-FRN100000384', '', 'Hall Properties', '', 'Supplier', '', '', '55642', 'Hall Properties', 'P.O.Box 357', 'South Orange', '07079', 'NJ', NULL, 'Hall Properties', '(973) 555-3827', '', '2018-01-03', '2017-05-23T13:43:08-07:00', '40', 'quickbook-FRN100000384-40'),
(13, 'FRN100000384', 'quickbook-FRN100000384', '', 'Hicks Hardware', '', 'Supplier', '', '', '556223', 'Hicks Hardware', '42 Main St.', 'Middlefield', '94303', 'CA', NULL, 'Hicks Hardware', '(650) 554-1973', '', '2018-01-03', '2017-05-23T13:01:57-07:00', '41', 'quickbook-FRN100000384-41'),
(14, 'FRN100000384', 'quickbook-FRN100000384', '', 'Lee Advertising', '', 'Supplier', '', '', '776543', 'Lee Advertising', '53 Main St.', 'Middlefield', '94303', 'CA', NULL, 'Lee Advertising', '(650) 554-4622', '', '2018-01-03', '2017-05-17T10:28:25-07:00', '42', 'quickbook-FRN100000384-42'),
(15, 'FRN100000384', 'quickbook-FRN100000384', '', 'Mahoney Mugs', '', 'Supplier', '', '', '', 'Mahoney Mugs', '', '', '', '', NULL, 'Mahoney Mugs', '', '', '2018-01-03', '2017-05-17T10:28:46-07:00', '43', 'quickbook-FRN100000384-43'),
(16, 'FRN100000384', 'quickbook-FRN100000384', '', 'Met Life Dental', '', 'Supplier', '', '', '', 'Met Life Dental', '', '', '', '', NULL, 'Met Life Dental', '', '', '2018-01-03', '2017-05-17T10:29:10-07:00', '44', 'quickbook-FRN100000384-44'),
(17, 'FRN100000384', 'quickbook-FRN100000384', '', 'MyVendcomp', '', 'Supplier', '', '', '', 'Mr Neerajvend m kuamr', '#786', 'chandigarh1', '160050', 'chandigarh2', NULL, 'Mr Neerajvend m kuamr', '(777) 777-7777', 'neervend@gmail.com', '2018-01-03', '2017-07-28T01:05:45-07:00', '60', 'quickbook-FRN100000384-60'),
(18, 'FRN100000384', 'quickbook-FRN100000384', '', 'National Eye Care', '', 'Supplier', '', '', '164978565103482659421', 'National Eye Care', '123 Anywhere Ave', 'Tucson', '85704', 'AZ', NULL, 'National Eye Care', '(800) 555-5555', 'Nateyecare@intuit.com, pauliejones15@intuit.com', '2018-01-03', '2017-05-17T10:31:37-07:00', '45', 'quickbook-FRN100000384-45'),
(19, 'FRN100000384', 'quickbook-FRN100000384', '', 'Norton Lumber and Building Materials', '', 'Supplier', '', '', '32980256', 'Norton Lumber and Building Materials', '4528 Country Road', 'Middlefield', '94303', 'CA', NULL, 'Norton Lumber and Building Materials', '(650) 363-6578', 'Materials@intuit.com', '2018-01-03', '2017-05-24T13:10:36-07:00', '46', 'quickbook-FRN100000384-46'),
(20, 'FRN100000384', 'quickbook-FRN100000384', '', 'PG&E', '', 'Supplier', '', '', '00649587213', 'PG&E', '4 Main St.', 'Palo Alto', '94303', 'CA', NULL, 'PG&E', '(888) 555-9465', 'utilities@noemail.com', '2018-01-03', '2017-05-23T13:00:36-07:00', '48', 'quickbook-FRN100000384-48'),
(21, 'FRN100000384', 'quickbook-FRN100000384', '', 'Pam Seitz, CPA', '', 'Supplier', '', '', '64132549', 'Pam Seitz', 'P.O. Box 15', 'Bayshore', '94326', 'CA', NULL, 'Pam Seitz', '(650) 557-8855', 'SeitzCPA@noemail.com', '2018-01-03', '2017-05-17T10:35:10-07:00', '47', 'quickbook-FRN100000384-47'),
(22, 'FRN100000384', 'quickbook-FRN100000384', '', 'Robertson & Associates', '', 'Supplier', '', '', '000005641', 'Robertson & Associates', 'P.O. Box 147', 'Bayshore', '94326', 'CA', NULL, 'Robertson & Associates', '(650) 557-1111', '', '2018-01-03', '2017-05-24T12:36:59-07:00', '49', 'quickbook-FRN100000384-49'),
(23, 'FRN100000384', 'quickbook-FRN100000384', '', '', '', 'Supplier', '', '', '', 'Squeaky Kleen Car Wash', '', '', '', '', NULL, 'Squeaky Kleen Car Wash', '', '', '2018-01-03', '2017-06-07T14:29:35-07:00', '57', 'quickbook-FRN100000384-57'),
(24, 'FRN100000384', 'quickbook-FRN100000384', '', 'Tania\'s Nursery', '', 'Supplier', '', '', '2154', 'Tania\'s Nursery', '1111 Elm St.', 'Middlefield', '94482', 'CA', NULL, 'Tania\'s Nursery', '(886) 554-2265', 'plantqueen@taniasnursery.com', '2018-01-03', '2017-05-22T15:10:33-07:00', '50', 'quickbook-FRN100000384-50'),
(25, 'FRN100000384', 'quickbook-FRN100000384', '', 'Tim Philip Masonry', '', 'Supplier', '', '', '0078965', 'Tim Philip Masonry', '3948 Elm St.', 'Middlefield', '94482', 'CA', NULL, 'Tim Philip Masonry', '(800) 556-1254', 'tim.philip@timphilipmasonry.com', '2018-01-03', '2017-05-23T13:06:58-07:00', '51', 'quickbook-FRN100000384-51'),
(26, 'FRN100000384', 'quickbook-FRN100000384', '', '', '', 'Supplier', '', '', '', 'Tony Rondonuwu', '', '', '', '', NULL, 'Tony Rondonuwu', '', 'tonyrjr@intuit.com', '2018-01-03', '2017-05-17T10:43:57-07:00', '52', 'quickbook-FRN100000384-52'),
(27, 'FRN100000384', 'quickbook-FRN100000384', '', '', '', 'Supplier', '', '', '00000111546', 'United States Treasury', '5568 Capital Drive', 'Tucson', '85718', 'AZ', NULL, 'United States Treasury', '(888) 555-6454', 'taxesaregreat@intuit.com', '2018-01-03', '2017-05-17T10:46:00-07:00', '53', 'quickbook-FRN100000384-53'),
(28, 'FRN100000384', 'quickbook-FRN100000384', '', 'kapil pvt. ltd.', '', 'Supplier', '', '', '', 'mr kapil dev dharnia', '', '', '', '', NULL, 'mr kapil dev dharnia', '45345345', 'kapil@1wayit.com', '2018-01-03', '2017-07-28T01:34:53-07:00', '61', 'quickbook-FRN100000384-61');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appcrontracker`
--
ALTER TABLE `appcrontracker`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `appDetail`
--
ALTER TABLE `appDetail`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `synccredential`
--
ALTER TABLE `synccredential`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `syncinvoice`
--
ALTER TABLE `syncinvoice`
  ADD PRIMARY KEY (`syncinvoice_id`);

--
-- Indexes for table `syncinvoice_item`
--
ALTER TABLE `syncinvoice_item`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `updateId` (`updateId`);

--
-- Indexes for table `syncledger_transaction`
--
ALTER TABLE `syncledger_transaction`
  ADD PRIMARY KEY (`syncledgertransaction_id`);

--
-- Indexes for table `syncnominal`
--
ALTER TABLE `syncnominal`
  ADD PRIMARY KEY (`syncnominal_id`);

--
-- Indexes for table `syncreport_pl`
--
ALTER TABLE `syncreport_pl`
  ADD PRIMARY KEY (`syncreport_pl_id`);

--
-- Indexes for table `syncsupplier`
--
ALTER TABLE `syncsupplier`
  ADD PRIMARY KEY (`syncsupplier_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appcrontracker`
--
ALTER TABLE `appcrontracker`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
--
-- AUTO_INCREMENT for table `appDetail`
--
ALTER TABLE `appDetail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT for table `synccredential`
--
ALTER TABLE `synccredential`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;
--
-- AUTO_INCREMENT for table `syncinvoice`
--
ALTER TABLE `syncinvoice`
  MODIFY `syncinvoice_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;
--
-- AUTO_INCREMENT for table `syncinvoice_item`
--
ALTER TABLE `syncinvoice_item`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;
--
-- AUTO_INCREMENT for table `syncledger_transaction`
--
ALTER TABLE `syncledger_transaction`
  MODIFY `syncledgertransaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;
--
-- AUTO_INCREMENT for table `syncnominal`
--
ALTER TABLE `syncnominal`
  MODIFY `syncnominal_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;
--
-- AUTO_INCREMENT for table `syncreport_pl`
--
ALTER TABLE `syncreport_pl`
  MODIFY `syncreport_pl_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT for table `syncsupplier`
--
ALTER TABLE `syncsupplier`
  MODIFY `syncsupplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
