-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th8 13, 2025 lúc 07:43 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `seo01_food`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `carts`
--

CREATE TABLE `carts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `carts`
--

INSERT INTO `carts` (`id`, `user_id`, `created_at`) VALUES
(4, 6, '2025-07-28 01:17:51'),
(5, 7, '2025-08-01 10:20:29'),
(6, 8, '2025-08-06 06:32:25');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `cart_items`
--

INSERT INTO `cart_items` (`id`, `cart_id`, `product_id`, `quantity`) VALUES
(13, 4, 1, 1),
(15, 5, 1, 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `shipping_address` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `status`, `shipping_address`, `created_at`) VALUES
(5, 6, 50000.00, 'pending', 'O', '2025-07-28 01:28:06'),
(6, 7, 50000.00, 'pending', 'a', '2025-08-01 10:21:21'),
(7, 6, 50000.00, 'pending', '1', '2025-08-01 12:12:23'),
(8, 6, 60000.00, 'pending', '1', '2025-08-02 15:30:22'),
(9, 6, 70000.00, 'pending', 'a', '2025-08-02 15:43:41'),
(10, 6, 35000.00, 'pending', 'a', '2025-08-02 15:51:25'),
(11, 6, 220000.00, 'completed', '44 ngo 406 xuan phuong', '2025-08-03 06:02:42'),
(12, 6, 35000.00, 'pending', 'a', '2025-08-06 06:24:12'),
(13, 8, 35000.00, 'pending', 'a', '2025-08-06 06:32:46');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(8, 5, 1, 1, 50000.00),
(9, 6, 1, 1, 50000.00),
(10, 7, 1, 1, 50000.00),
(11, 8, 2, 1, 30000.00),
(12, 8, 3, 1, 30000.00),
(13, 9, 1, 2, 35000.00),
(14, 10, 1, 1, 35000.00),
(15, 11, 1, 1, 35000.00),
(16, 11, 19, 1, 45000.00),
(17, 11, 23, 1, 20000.00),
(18, 11, 22, 1, 90000.00),
(19, 11, 2, 1, 30000.00),
(20, 12, 1, 1, 35000.00),
(21, 13, 1, 1, 35000.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `card_number` varchar(16) NOT NULL,
  `expiry_date` varchar(5) NOT NULL,
  `cvv` varchar(4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `user_id`, `card_number`, `expiry_date`, `cvv`, `created_at`, `updated_at`) VALUES
(1, 8, '1111111111111111', '12/12', '1231', '2025-08-06 06:36:45', '2025-08-06 06:36:45');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `loai_banh_keo` varchar(100) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `image`, `created_at`, `loai_banh_keo`) VALUES
(1, 'Matcha Latte Việt Quất', 'OK', 35000.00, 'uploads/1754146830_vn-11134517-7ra0g-m9os1xye5s0q7d@resize_ss400x400!@crop_w400_h400_cT.jpg', '2025-07-22 14:42:50', 'nước uống'),
(2, 'Matcha Latte', 'OK', 30000.00, 'uploads/1754146893_vn-11134517-7ra0g-m9gdqd4gxutg4f@resize_ss400x400!@crop_w400_h400_cT.jpg', '2025-07-22 14:42:50', 'nước uống'),
(3, 'Khoai Môn Latte', 'OK', 30000.00, 'uploads/1754146931_vn-11134517-7ra0g-m9os0pcffwuy34@resize_ss400x400!@crop_w400_h400_cT.jpg', '2025-07-22 14:42:50', 'nước uống'),
(16, 'Cacao Latte', 'OK', 30000.00, 'uploads/1754146979_vn-11134517-7ra0g-m9os11qvalpqc5@resize_ss400x400!@crop_w400_h400_cT.jpg', '2025-07-28 01:15:46', 'nước uống'),
(17, 'Matcha Latte Kem Muối', 'OK', 30000.00, 'uploads/1754147018_vn-11134517-7ra0g-m9gegq0scdac37@resize_ss400x400!@crop_w400_h400_cT.jpg', '2025-07-28 01:25:21', 'nước uống'),
(18, 'Mattcha Latte Sữa Gấu', 'OK', 30000.00, 'uploads/1754147051_vn-11134517-7ra0g-m9orwmpo0qoq51@resize_ss400x400!@crop_w400_h400_cT.jpg', '2025-07-28 01:26:17', 'nước uống'),
(19, 'Sinh Tố Bơ', 'OK', 45000.00, 'uploads/1754147084_vn-11134517-7ra0g-m8kmxd0wgnd086@resize_ss400x400!@crop_w400_h400_cT.jpg', '2025-07-28 02:10:01', 'nước uống'),
(20, 'CB:1/2 hun khói+ 100g chân giò hun khói+5 nem chua rán + tặng 1coca 390ml', 'OK', 220000.00, 'uploads/1754147157_vn-11134517-7r98o-lwzpi1d2jh09e3@resize_ss400x400!@crop_w400_h400_cT.jpg', '2025-08-01 11:38:38', 'đồ ăn'),
(21, 'CB: 1 đùi kfc + 1 cánh kfc + 5 nem chua', 'OK', 160000.00, 'uploads/1754147201_vn-11134517-7r98o-lwu5t2387zdlbf@resize_ss400x400!@crop_w400_h400_cT.jpg', '2025-08-01 11:46:30', 'đồ ăn'),
(22, '1/2 GÀ Ủ MUỐI HOA TIÊU ~600g đã chín', 'OK', 90000.00, 'uploads/1754147244_vn-11134517-7r98o-lt68rx6nnpnd9a@resize_ss400x400!@crop_w400_h400_cT.jpg', '2025-08-01 12:16:07', 'đồ ăn'),
(23, 'Xôi ruốc heo hành phi', 'OK', 20000.00, 'uploads/1754147276_vn-11134517-7ras8-mbxi56wojetx3e@resize_ss400x400!@crop_w400_h400_cT.jpg', '2025-08-01 12:16:56', 'đồ ăn'),
(24, 'a', 'a', 1.00, 'uploads/1754462788_vn-11134517-7r98o-lt68rx6nnpnd9a@resize_ss400x400!@crop_w400_h400_cT.jpg', '2025-08-06 06:46:28', 'đồ ăn');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL DEFAULT 'Người dùng',
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password`, `role`, `created_at`) VALUES
(6, 'Trương Ngọc Hòaaaf', 'h@gmail.com', '0999999999', '$2y$10$P5GCBs2P25alVWhLV/Epcu9NMDmKbx8kGUmJmjywxRo2B6Z0Hom0C', 'admin', '2025-07-28 01:17:39'),
(7, 'Trương Ngọc Hòa', 'hn@gmail.com', NULL, '$2y$10$/8Nw2LDOe/hwA1po1d3VQOgYREMaTrPcKoh0UvMd3p51QDcrmz8uy', 'admin', '2025-08-01 10:20:26'),
(8, 'hoa ngoc', 'qn@gmail.com', NULL, '$2y$10$6TlcfQO9pkbxGNoxPUuNoOoI7f2.2IqjPLUgVQappb8j8bkZ6T9Vi', 'user', '2025-08-06 06:32:18');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_id` (`cart_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT cho bảng `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT cho bảng `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD CONSTRAINT `payment_methods_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
