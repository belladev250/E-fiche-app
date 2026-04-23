import * as React from "react"
import { clsx, type ClassValue } from "clsx"
import { twMerge } from "tailwind-merge"

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

export const Card = ({ className, ...props }: any) => (
  <div className={cn("bg-white text-card-foreground border shadow-sm", className)} {...props} />
)

export const CardContent = ({ className, ...props }: any) => (
  <div className={cn("p-6 pt-0", className)} {...props} />
)

export const Button = React.forwardRef(({ className, variant, ...props }: any, ref: any) => {
  const variants = {
    default: "bg-blue-600 text-white hover:bg-blue-700",
    secondary: "bg-gray-100 text-gray-900 hover:bg-gray-200",
    outline: "border border-gray-200 bg-transparent hover:bg-gray-100",
  }
  const v = (variant as keyof typeof variants) || "default"
  return (
    <button
      ref={ref}
      className={cn("inline-flex items-center justify-center rounded-md font-medium transition-colors h-10 px-4 py-2 disabled:opacity-50", variants[v], className)}
      {...props}
    />
  )
})
