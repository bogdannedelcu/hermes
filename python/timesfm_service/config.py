"""Centralized configuration loaded from environment variables."""
import os
from dotenv import load_dotenv

load_dotenv()


class Config:
    # Service
    HOST      = os.environ.get("HOST", "127.0.0.1")
    PORT      = int(os.environ.get("PORT", "8081"))
    LOG_LEVEL = os.environ.get("LOG_LEVEL", "info")

    # Inference tuning
    MAX_CONTEXT     = int(os.environ.get("MAX_CONTEXT", "2048"))
    MAX_BATCH_SIZE  = int(os.environ.get("MAX_BATCH_SIZE", "32"))
    REQUEST_TIMEOUT = int(os.environ.get("REQUEST_TIMEOUT", "180"))


config = Config()
