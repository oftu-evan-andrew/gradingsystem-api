# GradingSystem API Architecture & Engineering Documentation

This document provides a comprehensive overview of the architecture, resource engineering, and technical mechanisms powering the GradingSystem API built with Laravel.

## 1. Architectural Overview

The GradingSystem API follows a classical **MVC (Model-View-Controller)** architecture adapted for an API-only backend. 
- **Routing**: API routes are defined in `routes/api.php` and map to resource controllers.
- **Controllers**: Handle HTTP requests, perform authorization checks (often via middleware), interact with Models to retrieve/modify data, and return JSON responses.
- **Models**: Built on Eloquent ORM. They define the database schema relationships and contain the core business logic using Eloquent lifecycle hooks (events) for automatic calculations.
- **Services**: Complex business logic, specifically grade calculations, is delegated to dedicated service classes like `GradeCalculationService`.

The architecture implements a cascading calculation pattern using Eloquent `booted` lifecycle events triggering a domino effect from basic records up to a student's final GPA.

---

## 2. Resource Entities and Database Schema

The API's data structure can be logically grouped into four major domains:

### A. Identity & Access Management
- **`User`**: Base entity for authentication. Manages credentials and roles.
- **`Professor`**: Belongs to a User. Represents faculty members who handle grading.
- **`Student`**: Belongs to a User. Represents the entity being graded.

### B. Academic Structure
- **`Course`**: Represents degree programs (e.g., BS Computer Science).
- **`Subject`**: Represents academic subjects (e.g., CS101).
- **`Section`**: A specific block or cohort of students taking a Course.
- **`SectionSubject`**: The junction linking a Section to a Subject and assigned to a specific Professor for an academic term.

### C. Raw Assessment Records
These are the foundational grading entities. Professors input grades directly into these records.
- **`AttendanceRecord`**
- **`RecitationRecord`**
- **`QuizRecord`**
- **`ProjectRecord`**

### D. Computed Grade Aggregations
These entities aggregate the raw records and are mostly calculated automatically by the system.
- **`ClassStanding`**: Aggregates the raw assessment records (Attendance, Recitations, Quizzes, Projects, Major Exams) for a specific grading period (e.g., Prelims, Midterms).
- **`PeriodicGrade`**: Computed from the `ClassStanding` based on configured grade weights for that specific grading period.
- **`StudentFinalGrade`**: The final averaged grade of a student for the entire subject across all periodic grades.
- **`StudentGpa`**: The overall cumulative Grade Point Average of a student across all completed subjects.

---

## 3. Engineering Mechanisms & The Calculation Cascade

A core engineering feature of the GradingSystem API is **Automatic Grade Cascading**. Instead of running massive cron jobs or requiring manual triggers for every level of calculation, the system takes advantage of Eloquent `booted()` event listeners (`static::saved` and `static::updated`).

### The Domino Effect

1. **Raw Input**: A Professor creates/updates a `QuizRecord` (or Attendance, Recitation, Project).
2. **Class Standing Aggregation**: In the `booted()` method of `QuizRecord`, the system captures the `saved` event, recalculates the student's total Quiz score for that period, and updates the `ClassStanding` model.
3. **Periodic Grade Calculation**: When the `ClassStanding` model saves, its `booted()` event triggers. It invokes the `GradeCalculationService` to compute the `PeriodicGrade` using the updated standing components. It automatically creates or updates the `PeriodicGrade` record.
4. **Final Grade Calculation**: When the `PeriodicGrade` is saved, its `booted()` event passes the updated data to the `GradeCalculationService` to calculate the `StudentFinalGrade` combining the various periods (Prelim, Midterm, Finals).
5. **GPA Calculation**: Finally, when a `StudentFinalGrade` is updated and its status is marked as `'finalized'`, the `booted()` method triggers the calculation of the `StudentGpa`, aggregating all finalized subject grades.

*This reactive architecture ensures that any change at the lowest level of data input instantaneously keeps all dependent high-level grade summaries accurate without manual intervention.*

---

## 4. API Routing & Endpoints Setup

Routes are registered in `routes/api.php` under the `auth:sanctum` middleware block, utilizing Laravel Sanctum for API token authentication.

### Authentication Endpoints
- `POST /auth/login`: Authenticate and receive a Sanctum token.
- `POST /auth/register`: Create new user accounts.
- `POST /auth/logout`: Revoke tokens.

### Standard Resource Endpoints
The backend heavily uses Laravel's `Route::apiResource()` to automatically generate standard CRUD endpoints `[GET (index), GET (show), POST (store), PUT/PATCH (update), DELETE (destroy)]` for:
- `/courses`, `/subjects`, `/sections`, `/professors`, `/students`, `/section-subjects`
- `/attendance-records`, `/recitation-records`, `/quiz-records`, `/project-records`
- `/class-standings`, `/periodic-grades`, `/student-final-grades`

### Action-Specific Endpoints
Special operational routes bypass the standard CRUD to perform specific business actions, primarily focusing on grade finalization processes:
- `POST /class-standings/{id}/finalize`
- `POST /periodic-grades/{id}/finalize`
- `POST /student-final-grades/{id}/finalize`
- **Bulk Operations**: `POST /class-standings/bulk/finalize` and `POST /student-final-grades/bulk/approve` allowing admins/professors to finalize multiple students at once.
- **Manual Triggers**: `POST /student-gpas/calculate`

### Access Control
- Middleware `can:access-professor-content` protects grading input endpoints (`/quiz-records`, etc.) ensuring only authorized professor accounts can manipulate un-finalized raw assessment records.
