import sys
import cv2
import numpy as np
import easyocr
import json
import os

def order_points(pts):
    """
    Mengurutkan 4 titik sudut persegi panjang dalam urutan:
    top-left, top-right, bottom-right, bottom-left.
    """
    rect = np.zeros((4, 2), dtype="float32")

    s = pts.sum(axis=1)
    rect[0] = pts[np.argmin(s)]
    rect[2] = pts[np.argmax(s)]
    diff = np.diff(pts, axis=1)
    rect[1] = pts[np.argmin(diff)]
    rect[3] = pts[np.argmax(diff)]

    return rect

def four_point_transform(image, pts):
    """
    Melakukan transformasi perspektif pada gambar menggunakan 4 titik.
    """
    rect = order_points(pts)
    (tl, tr, br, bl) = rect

    widthA = np.sqrt(((br[0] - bl[0]) ** 2) + ((br[1] - bl[1]) ** 2))
    widthB = np.sqrt(((tr[0] - tl[0]) ** 2) + ((tr[1] - tl[1]) ** 2))
    maxWidth = max(int(widthA), int(widthB))

    heightA = np.sqrt(((tr[0] - br[0]) ** 2) + ((tr[1] - br[1]) ** 2))
    heightB = np.sqrt(((tl[0] - bl[0]) ** 2) + ((tl[1] - bl[1]) ** 2))
    maxHeight = max(int(heightA), int(heightB))

    dst = np.array([
        [0, 0],
        [maxWidth - 1, 0],
        [maxWidth - 1, maxHeight - 1],
        [0, maxHeight - 1]], dtype="float32")

    M = cv2.getPerspectiveTransform(rect, dst)
    warped = cv2.warpPerspective(image, M, (maxWidth, maxHeight))

    return warped

def process_ktp_image(image_path):
    """
    Fungsi utama untuk mendeteksi, memotong, dan melakukan OCR pada gambar KTP.
    """
    try:
        img = cv2.imread(image_path)
        if img is None:
            return {"error": "Could not read image. Check path or file corruption."}

        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
        blurred = cv2.GaussianBlur(gray, (5, 5), 0)
        edged = cv2.Canny(blurred, 75, 200)
        contours, _ = cv2.findContours(edged.copy(), cv2.RETR_LIST, cv2.CHAIN_APPROX_SIMPLE)
        contours = sorted(contours, key=cv2.contourArea, reverse=True)

        ktp_contour = None
        for c in contours:
            peri = cv2.arcLength(c, True)
            approx = cv2.approxPolyDP(c, 0.02 * peri, True)
            if len(approx) == 4:
                x, y, w, h = cv2.boundingRect(approx)
                aspect_ratio = float(w) / h
                if 1.4 < aspect_ratio < 1.8 and w > img.shape[1] * 0.4 and h > img.shape[0] * 0.2:
                    ktp_contour = approx
                    break

        cropped_ktp_image = None
        if ktp_contour is not None:
            cropped_ktp_image = four_point_transform(img, ktp_contour.reshape(4, 2))
            target_width = 1000
            target_height = int(target_width / 1.58)
            cropped_ktp_image = cv2.resize(cropped_ktp_image, (target_width, target_height), interpolation=cv2.INTER_AREA)
        else:
            cropped_ktp_image = img

        if cropped_ktp_image is None:
            return {"error": "Failed to process image for OCR."}

        reader = easyocr.Reader(['en', 'id'])
        results = reader.readtext(cropped_ktp_image)

        ocr_data = {}
        full_text = []
        for (bbox, text, prob) in results:
            full_text.append(text)
            if "NIK" in text.upper() or len(text) == 16 and text.isdigit():
                if 'nik' not in ocr_data:
                    ocr_data['nik'] = text.replace(' ', '')

        ocr_data['raw_text'] = " ".join(full_text)

        return {"status": "success", "ocr_data": ocr_data}

    except Exception as e:
        return {"error": f"An unexpected error occurred: {str(e)}"}

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No image path provided."}))
        sys.exit(1)

    image_path = sys.argv[1]
    result = process_ktp_image(image_path)
    print(json.dumps(result))
    sys.exit(0)
