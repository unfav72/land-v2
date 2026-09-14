from fastapi import FastAPI, UploadFile, File, HTTPException
from fastapi.middleware.cors import CORSMiddleware
import uvicorn
from pydantic import BaseModel
import uuid

app = FastAPI(title="Land Record OCR API")

# Configure CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # In production, specify the allowed origins
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

class OCRResponse(BaseModel):
    document_id: str
    status: str
    fields: list
    message: str = ""

@app.get("/health")
def health_check():
    return {"status": "healthy"}

@app.post("/process-document", response_model=OCRResponse)
async def process_document(file: UploadFile = File(...)):
    if not file.filename:
        raise HTTPException(status_code=400, detail="No file uploaded")
    
    # In a real implementation:
    # 1. Save file temporarily
    # 2. Use OpenCV to preprocess (deskew, denoise)
    # 3. Use Tesseract to extract text
    # 4. Use Regex/NLP to find specific fields (survey_number, owner_name, etc.)
    # 5. Calculate confidence scores
    
    # Placeholder for MVP response structure
    return {
        "document_id": str(uuid.uuid4()),
        "status": "completed",
        "fields": [
            {
                "name": "survey_number",
                "value": "124/38A",
                "confidence": 0.85,
                "page": 1,
                "requires_review": False
            },
            {
                "name": "owner_name",
                "value": "Ramesh Kumar",
                "confidence": 0.65,
                "page": 1,
                "requires_review": True
            }
        ],
        "message": "Processing completed with dummy data."
    }

if __name__ == "__main__":
    uvicorn.run("main:app", host="0.0.0.0", port=8000, reload=True)
